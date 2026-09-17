<?php
/** @var array $user */
/** @var array $workspaces */
/** @var int $step */
?>
<div class="onboard">
    <div class="card onboard-card">
        <p class="eyebrow">Welcome to Nook</p>
        <?php if ($step === 1): ?>
            <h1>What should we call you?</h1>
            <form method="post" action="<?= e(url('/onboarding/name')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required>
                </label>
                <button class="btn btn-primary" type="submit">Continue</button>
            </form>
        <?php elseif ($step === 2): ?>
            <h1>Create your first workspace.</h1>
            <p class="muted">A workspace is a place for a company, product, book, podcast or anything you’re building.</p>
            <form method="post" action="<?= e(url('/onboarding/workspace')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" required placeholder="Frecuencia Paralela">
                </label>
                <label class="field">
                    <span>Type</span>
                    <select name="type">
                        <?php foreach (workspace_types() as $k => $label): ?>
                            <option value="<?= e($k) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>A sentence about it</span>
                    <textarea name="description" rows="3" placeholder="What is this, and why does it exist?"></textarea>
                </label>
                <button class="btn btn-primary" type="submit">Create workspace</button>
            </form>
        <?php else: ?>
            <h1>What are you working on today?</h1>
            <p class="muted">Optional. You can skip this and enter Nook quietly.</p>
            <form method="post" action="<?= e(url('/onboarding/task')) ?>" class="stack">
                <?= csrf_field() ?>
                <label class="field">
                    <span>First task</span>
                    <input type="text" name="title" placeholder="Record episode 001">
                </label>
                <button class="btn btn-primary" type="submit">Enter Nook</button>
            </form>
            <form method="post" action="<?= e(url('/onboarding/finish')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Skip for now</button>
            </form>
        <?php endif; ?>
    </div>
</div>
