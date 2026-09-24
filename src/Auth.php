<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cachedUser;
        }

        self::$loaded = true;
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return self::$cachedUser = null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, phone, role, previous_role, is_active, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int)$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            unset($_SESSION['user_id']);
            return self::$cachedUser = null;
        }

        if ($user['role'] === 'BLOCKED') {
            unset($_SESSION['user_id']);
            $_SESSION['_auth_notice'] = 'A tua conta foi bloqueada por um administrador. Se achares que isto é um erro, contacta o suporte da SHIFT.';
            return self::$cachedUser = null;
        }

        if ($user['role'] === 'DELETED') {
            unset($_SESSION['user_id']);
            $_SESSION['_auth_notice'] = 'Esta conta foi eliminada e já não pode aceder à SHIFT.';
            return self::$cachedUser = null;
        }

        if (!(int)$user['is_active']) {
            unset($_SESSION['user_id']);
            $_SESSION['_auth_notice'] = 'Esta conta encontra-se indisponível. Contacta o suporte da SHIFT.';
            return self::$cachedUser = null;
        }

        return self::$cachedUser = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int)self::user()['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'ADMIN';
    }

    public static function attempt(string $email, string $password): string
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return 'INVALID';
        }

        if ($user['role'] === 'BLOCKED') {
            return 'BLOCKED';
        }

        if ($user['role'] === 'DELETED') {
            return 'DELETED';
        }

        if (!(int)$user['is_active']) {
            return 'INACTIVE';
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        self::refresh();
        CartService::mergeGuestCartIntoUser((int)$user['id']);

        return 'SUCCESS';
    }

    public static function login(string $email, string $password): bool
    {
        return self::attempt($email, $password) === 'SUCCESS';
    }

    public static function refresh(): void
    {
        self::$loaded = false;
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        self::refresh();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['login_redirect'] = current_path();
            redirect('auth/login.php');
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Acesso reservado a administradores.');
        }
    }
}
