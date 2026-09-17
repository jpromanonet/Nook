<?php

declare(strict_types=1);

final class DailyService
{
    public static function forDate(string $date): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM daily_notes WHERE user_id = :uid AND note_date = :d LIMIT 1'
        );
        $stmt->execute(['uid' => Auth::id(), 'd' => $date]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return [
            'note_date' => $date,
            'plan' => '',
            'must_do' => '',
            'should_do' => '',
            'if_time' => '',
            'notes' => '',
            'learned' => '',
            'decisions' => '',
            'ideas' => '',
            'completed' => '',
            'moved_tomorrow' => '',
        ];
    }

    public static function save(string $date, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO daily_notes
                (user_id, note_date, plan, must_do, should_do, if_time, notes, learned, decisions, ideas, completed, moved_tomorrow)
             VALUES
                (:uid, :d, :plan, :must_do, :should_do, :if_time, :notes, :learned, :decisions, :ideas, :completed, :moved)
             ON DUPLICATE KEY UPDATE
                plan = VALUES(plan), must_do = VALUES(must_do), should_do = VALUES(should_do),
                if_time = VALUES(if_time), notes = VALUES(notes), learned = VALUES(learned),
                decisions = VALUES(decisions), ideas = VALUES(ideas), completed = VALUES(completed),
                moved_tomorrow = VALUES(moved_tomorrow)'
        );
        $stmt->execute([
            'uid' => Auth::id(),
            'd' => $date,
            'plan' => null_if_blank($data['plan'] ?? null),
            'must_do' => null_if_blank($data['must_do'] ?? null),
            'should_do' => null_if_blank($data['should_do'] ?? null),
            'if_time' => null_if_blank($data['if_time'] ?? null),
            'notes' => null_if_blank($data['notes'] ?? null),
            'learned' => null_if_blank($data['learned'] ?? null),
            'decisions' => null_if_blank($data['decisions'] ?? null),
            'ideas' => null_if_blank($data['ideas'] ?? null),
            'completed' => null_if_blank($data['completed'] ?? null),
            'moved' => null_if_blank($data['moved_tomorrow'] ?? null),
        ]);
    }

    public static function todaySnapshot(): array
    {
        $today = date('Y-m-d');
        $due = ItemService::list([
            'type' => 'task',
            'due_on' => $today,
            'order' => "FIELD(i.status,'doing','todo','blocked','inbox'), i.priority = 'urgent' DESC, i.title",
            'limit' => 30,
        ]);
        $overdue = ItemService::list([
            'type' => 'task',
            'due_before' => $today,
            'order' => 'i.due_date, i.title',
            'limit' => 20,
        ]);
        $overdue = array_values(array_filter($overdue, static function ($row) {
            return !in_array($row['status'], ['done', 'cancelled'], true);
        }));
        $events = ItemService::list([
            'type' => 'event',
            'due_from' => $today,
            'due_to' => $today,
            'order' => 'COALESCE(i.start_date, i.due_date), i.title',
            'limit' => 20,
        ]);
        $recentDocs = ItemService::list([
            'type' => ['document', 'note'],
            'order' => 'i.updated_at DESC',
            'limit' => 5,
        ]);

        return [
            'date' => $today,
            'plan' => self::forDate($today),
            'due' => $due,
            'overdue' => $overdue,
            'events' => $events,
            'recent_docs' => $recentDocs,
        ];
    }
}
