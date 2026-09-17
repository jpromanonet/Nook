<?php

declare(strict_types=1);

final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/home');
        }
        view('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public static function login(): void
    {
        require_csrf();
        $email = trim((string) input('email', ''));
        $password = (string) input('password', '');
        $remember = !empty($_POST['remember']);

        if ($email === '' || $password === '') {
            flash('error', 'Ingresá tu correo y contraseña.');
            redirect('/login');
        }

        if (!Auth::attempt($email, $password, $remember)) {
            if (Auth::isLoginLocked()) {
                flash('error', 'Demasiados intentos. Esperá unos minutos.');
            } else {
                flash('error', 'Correo o contraseña incorrectos.');
            }
            redirect('/login');
        }

        flash('success', 'Welcome back.');
        if (!(int) ($_SESSION['user']['onboarding_completed'] ?? 0)) {
            redirect('/onboarding');
        }
        redirect('/home');
    }

    public static function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/home');
        }
        view('auth/register', ['title' => 'Create account'], 'layouts/auth');
    }

    public static function register(): void
    {
        require_csrf();
        try {
            $password = (string) input('password', '');
            $passwordConfirm = (string) input('password_confirm', '');
            if ($password !== $passwordConfirm) {
                throw new InvalidArgumentException('Las contraseñas no coinciden.');
            }
            $user = Auth::register(
                (string) input('name', ''),
                (string) input('email', ''),
                $password
            );
            Auth::loginUser($user);
            flash('success', 'Welcome to Nook.');
            redirect('/onboarding');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/register');
        }
    }

    public static function logout(): void
    {
        require_csrf();
        Auth::logout();
        flash('success', 'Sesión cerrada.');
        redirect('/login');
    }

    public static function showForgot(): void
    {
        if (Auth::check()) {
            redirect('/home');
        }
        view('auth/forgot', ['title' => 'Forgot password'], 'layouts/auth');
    }

    public static function forgot(): void
    {
        require_csrf();
        $email = trim((string) input('email', ''));
        $plain = null;
        if ($email !== '') {
            $plain = UserService::createResetToken($email);
        }
        $msg = 'If that email exists, we sent a recovery link.';
        if ($plain && app_config('debug')) {
            $msg .= ' Link: ' . url('/reset-password?token=' . urlencode($plain));
        }
        flash('success', $msg);
        redirect('/forgot-password');
    }

    public static function showReset(): void
    {
        if (Auth::check()) {
            redirect('/home');
        }
        $token = (string) input('token', '');
        if ($token === '') {
            flash('error', 'Enlace incompleto.');
            redirect('/forgot-password');
        }
        view('auth/reset', ['title' => 'New password', 'token' => $token], 'layouts/auth');
    }

    public static function reset(): void
    {
        require_csrf();
        try {
            UserService::resetPassword(
                (string) input('token', ''),
                (string) input('password', ''),
                (string) input('password_confirm', '')
            );
            flash('success', 'Contraseña actualizada. Iniciá sesión.');
            redirect('/login');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/reset-password?token=' . urlencode((string) input('token', '')));
        }
    }
}
