<?php $section = $section ?? 'profile'; ?>
<nav class="settings-nav">
    <a class="<?= $section === 'profile' ? 'is-active' : '' ?>" href="<?= e(url('/settings/profile')) ?>">Profile</a>
    <a class="<?= $section === 'account' ? 'is-active' : '' ?>" href="<?= e(url('/settings/account')) ?>">Account</a>
    <a class="<?= $section === 'appearance' ? 'is-active' : '' ?>" href="<?= e(url('/settings/appearance')) ?>">Appearance</a>
    <a class="<?= $section === 'security' ? 'is-active' : '' ?>" href="<?= e(url('/settings/security')) ?>">Security</a>
</nav>
