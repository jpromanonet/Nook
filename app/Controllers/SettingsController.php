<?php

declare(strict_types=1);

final class SettingsController
{
    public static function index(): void
    {
        redirect('/settings/profile');
    }

    public static function profile(): void
    {
        Auth::requireLogin();
        UserService::refreshSession();
        view('settings/profile', [
            'title' => 'Profile',
            'section' => 'profile',
            'account' => UserService::find(Auth::id()),
        ]);
    }

    public static function saveProfile(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            UserService::updateName(Auth::id(), (string) input('name', ''));
            $email = trim((string) input('email', ''));
            $current = UserService::find(Auth::id());
            if ($current && strtolower($email) !== strtolower((string) $current['email'])) {
                UserService::updateEmail(Auth::id(), $email, (string) input('current_password', ''));
            }
            flash('success', 'Profile updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/settings/profile');
    }

    public static function avatar(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            UserService::uploadAvatar(Auth::id(), $_FILES['avatar'] ?? []);
            flash('success', 'Photo updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/settings/profile');
    }

    public static function avatarRemove(): void
    {
        Auth::requireLogin();
        require_csrf();
        UserService::removeAvatar(Auth::id());
        flash('success', 'Photo removed.');
        redirect('/settings/profile');
    }

    public static function account(): void
    {
        Auth::requireLogin();
        view('settings/account', [
            'title' => 'Account',
            'section' => 'account',
            'account' => UserService::find(Auth::id()),
        ]);
    }

    public static function deleteAccount(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            UserService::deleteAccount(Auth::id(), (string) input('current_password', ''));
            Auth::logout();
            flash('success', 'Account deleted.');
            redirect('/login');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/settings/account');
        }
    }

    public static function appearance(): void
    {
        Auth::requireLogin();
        view('settings/appearance', [
            'title' => 'Appearance',
            'section' => 'appearance',
            'account' => UserService::find(Auth::id()),
        ]);
    }

    public static function saveAppearance(): void
    {
        Auth::requireLogin();
        require_csrf();
        Auth::setTheme((string) input('theme', 'system'));
        flash('success', 'Saved.');
        redirect('/settings/appearance');
    }

    public static function theme(): void
    {
        Auth::requireLogin();
        require_csrf();
        $theme = (string) input('theme', 'light');
        Auth::setTheme($theme);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (($_SERVER['HTTP_ACCEPT'] ?? '') && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'theme' => $theme]);
            exit;
        }
        redirect('/settings/appearance');
    }

    public static function security(): void
    {
        Auth::requireLogin();
        view('settings/security', [
            'title' => 'Security',
            'section' => 'security',
            'account' => UserService::find(Auth::id()),
            'sessions' => UserService::sessions(Auth::id()),
            'currentToken' => isset($_SESSION['_nook_sid']) ? hash('sha256', (string) $_SESSION['_nook_sid']) : '',
        ]);
    }

    public static function password(): void
    {
        Auth::requireLogin();
        require_csrf();
        try {
            UserService::changePassword(
                Auth::id(),
                (string) input('current_password', ''),
                (string) input('new_password', ''),
                (string) input('new_password_confirm', '')
            );
            flash('success', 'Password updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/settings/security');
    }

    public static function revokeSession(string $id): void
    {
        Auth::requireLogin();
        require_csrf();
        UserService::revokeSession(Auth::id(), (int) $id);
        flash('success', 'Session revoked.');
        redirect('/settings/security');
    }
}
