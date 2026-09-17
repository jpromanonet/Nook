<?php

declare(strict_types=1);

final class UserService
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, email, theme, avatar, status, onboarding_completed, created_at, last_login_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function refreshSession(): void
    {
        if (!Auth::check()) {
            return;
        }
        $row = self::find(Auth::id());
        if (!$row) {
            return;
        }
        $_SESSION['user']['name'] = $row['name'];
        $_SESSION['user']['email'] = $row['email'];
        $_SESSION['user']['theme'] = $row['theme'] ?: 'system';
        $_SESSION['user']['avatar'] = $row['avatar'] ?? null;
        $_SESSION['user']['onboarding_completed'] = (int) $row['onboarding_completed'];
    }

    public static function updateName(int $id, string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Ingresá un nombre.');
        }
        $stmt = Database::pdo()->prepare('UPDATE users SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
        $_SESSION['user']['name'] = $name;
    }

    public static function updateEmail(int $id, string $email, string $password): void
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ingresá un correo válido.');
        }
        self::assertPassword($id, $password);

        $exists = Database::pdo()->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $exists->execute(['email' => $email, 'id' => $id]);
        if ($exists->fetch()) {
            throw new InvalidArgumentException('Ese correo ya está en uso.');
        }

        $stmt = Database::pdo()->prepare('UPDATE users SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $email, 'id' => $id]);
        $_SESSION['user']['email'] = $email;
    }

    public static function changePassword(int $id, string $current, string $new, string $confirm): void
    {
        $min = (int) app_config('min_password', 8);
        if ($new !== $confirm) {
            throw new InvalidArgumentException('Las contraseñas nuevas no coinciden.');
        }
        if (strlen($new) < $min) {
            throw new InvalidArgumentException('La nueva contraseña debe tener al menos ' . $min . ' caracteres.');
        }
        self::assertPassword($id, $current);
        $upd = Database::pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $upd->execute([
            'hash' => password_hash($new, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
        self::revokeOtherSessions($id);
    }

    public static function uploadAvatar(int $id, array $file): string
    {
        $stored = UploadService::store(
            $file,
            'avatars',
            UploadService::avatarMap(),
            (int) app_config('max_avatar', 2 * 1024 * 1024)
        );
        $current = self::find($id);
        if ($current && !empty($current['avatar'])) {
            UploadService::delete('avatars', (string) $current['avatar']);
        }
        $stmt = Database::pdo()->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
        $stmt->execute(['avatar' => $stored['stored'], 'id' => $id]);
        $_SESSION['user']['avatar'] = $stored['stored'];
        return $stored['stored'];
    }

    public static function removeAvatar(int $id): void
    {
        $current = self::find($id);
        if ($current && !empty($current['avatar'])) {
            UploadService::delete('avatars', (string) $current['avatar']);
        }
        $stmt = Database::pdo()->prepare('UPDATE users SET avatar = NULL WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $_SESSION['user']['avatar'] = null;
    }

    public static function completeOnboarding(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET onboarding_completed = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $_SESSION['user']['onboarding_completed'] = 1;
    }

    public static function createResetToken(string $email): ?string
    {
        $email = strtolower(trim($email));
        $stmt = Database::pdo()->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        Database::pdo()->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL'
        )->execute(['uid' => $user['id']]);

        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);
        $ins = Database::pdo()->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 2 HOUR))'
        );
        $ins->execute(['uid' => $user['id'], 'hash' => $hash]);

        $link = url('/reset-password?token=' . urlencode($plain));
        $body = "Hola {$user['name']},\n\nPara elegir una contraseña nueva en Nook, usá este enlace (vence en 2 horas):\n{$link}\n\nSi no pediste esto, ignorá este mensaje.\n";
        MailService::send((string) $user['email'], 'Recuperar contraseña · Nook', $body);
        return $plain;
    }

    public static function resetPassword(string $token, string $password, string $confirm): void
    {
        $min = (int) app_config('min_password', 8);
        if ($password !== $confirm) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }
        if (strlen($password) < $min) {
            throw new InvalidArgumentException('La contraseña debe tener al menos ' . $min . ' caracteres.');
        }
        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('El enlace no es válido o ya venció.');
        }

        $upd = Database::pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $upd->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $row['user_id'],
        ]);
        Database::pdo()->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id')
            ->execute(['id' => $row['id']]);
        self::revokeOtherSessions((int) $row['user_id']);
    }

    public static function sessions(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM user_sessions WHERE user_id = :uid AND revoked_at IS NULL ORDER BY last_seen_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function revokeSession(int $userId, int $sessionId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $sessionId, 'uid' => $userId]);
    }

    public static function deleteAccount(int $id, string $password): void
    {
        self::assertPassword($id, $password);
        $user = self::find($id);
        if ($user && !empty($user['avatar'])) {
            UploadService::delete('avatars', (string) $user['avatar']);
        }
        $files = Database::pdo()->prepare(
            'SELECT stored_filename FROM items WHERE user_id = :uid AND stored_filename IS NOT NULL'
        );
        $files->execute(['uid' => $id]);
        foreach ($files->fetchAll() ?: [] as $row) {
            UploadService::delete('files', (string) $row['stored_filename']);
        }
        Database::pdo()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }

    private static function assertPassword(int $id, string $password): void
    {
        $stmt = Database::pdo()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            throw new InvalidArgumentException('La contraseña actual no es correcta.');
        }
    }

    private static function revokeOtherSessions(int $userId): void
    {
        $token = isset($_SESSION['_nook_sid']) ? hash('sha256', (string) $_SESSION['_nook_sid']) : '';
        $stmt = Database::pdo()->prepare(
            'UPDATE user_sessions SET revoked_at = NOW()
             WHERE user_id = :uid AND session_token <> :token AND revoked_at IS NULL'
        );
        $stmt->execute(['uid' => $userId, 'token' => $token]);
    }
}
