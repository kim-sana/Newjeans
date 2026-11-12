<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password): bool
    {
        session_start_safe();

        $statement = Database::pdo()->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = ? AND deleted_at IS NULL AND role = \'admin\'
             LIMIT 1'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!is_array($user) || !password_verify($password, (string) $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth_id'] = (int) $user['id'];

        unset($user['password']);
        self::$user = $user;
        self::$loaded = true;

        return true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;

        session_start_safe();

        $id = $_SESSION['auth_id'] ?? null;

        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return self::$user = null;
        }

        $statement = Database::pdo()->prepare(
            'SELECT id, name, email, role
             FROM users
             WHERE id = ? AND deleted_at IS NULL AND role = \'admin\'
             LIMIT 1'
        );
        $statement->execute([(int) $id]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            unset($_SESSION['auth_id']);
        }

        return self::$user = is_array($user) ? $user : null;
    }

    public static function logout(): void
    {
        session_start_safe();

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        self::$user = null;
        self::$loaded = true;
    }

    public static function requireAdmin(): void
    {
        if (self::check()) {
            return;
        }

        flash('error', 'Please sign in to access the admin panel.');
        redirect('/login');
    }
}
