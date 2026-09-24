<?php
declare(strict_types=1);

function app_url(string $path = ''): string
{
    global $config;
    $base = rtrim((string)$config['base_url'], '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float $value): string
{
    return number_format($value, 2, ',', '.') . ' €';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): void
{
    if (!$token || !hash_equals(csrf_token(), $token)) {
        json_response(['error' => 'Pedido inválido (CSRF).'], 419);
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) json_response(['error' => 'JSON inválido.'], 400);
    return $decoded;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function require_method(string ...$methods): void
{
    if (!in_array(request_method(), $methods, true)) {
        json_response(['error' => 'Método não permitido.'], 405);
    }
}

function setting(string $key, ?string $default = null): ?string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];

    try {
        $stmt = Database::connection()->prepare('SELECT value FROM store_settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        $cache[$key] = $value !== false ? (string)$value : $default;
    } catch (Throwable) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function shipping_price(): float { return (float)(setting('shipping_price', '4.90') ?? '4.90'); }
function free_shipping_threshold(): float { return (float)(setting('free_shipping_threshold', '75.00') ?? '75.00'); }

function current_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    if (empty($_SESSION['_flash'])) {
        unset($_SESSION['_flash']);
    }

    return is_string($value) ? $value : null;
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}
