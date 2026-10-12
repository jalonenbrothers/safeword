<?php
/**
 * lib/bootstrap.php — session + config + helpers, required by every page.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

function base_url(): string
{
    $cfg = config();
    return rtrim($cfg['base_url'], '/');
}

/** Redirect + exit. */
function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

/** Current family from session, or null. */
function current_family(): ?array
{
    if (empty($_SESSION['family_id'])) return null;
    $row = dbq('SELECT id, name, invite_token FROM families WHERE id = ?', [$_SESSION['family_id']])->fetch();
    return $row ?: null;
}

/** Require login; redirects to login.php with banner flag. */
function require_family(): array
{
    $f = current_family();
    if (!$f) {
        $_SESSION['flash_notice'] = 'Please sign in again.';
        redirect('login.php');
    }
    return $f;
}

/** Is the logged-in session a member-join session (not admin)? */
function session_member_id(): ?int
{
    return isset($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : null;
}

function flash_notice(): ?string
{
    $n = $_SESSION['flash_notice'] ?? null;
    unset($_SESSION['flash_notice']);
    return $n;
}

function set_flash(string $msg): void
{
    $_SESSION['flash_notice'] = $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!$t || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Bad request.');
    }
}
