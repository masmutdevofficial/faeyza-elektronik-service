<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key).'='.trim($value, " \t\n\r\0\x0B\"'"));
    }
}
load_env(ROOT . (getenv('APP_ENV') === 'testing' ? '/.env.testing' : '/.env'));
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT.'/storage/logs/php.log');
require_once ROOT.'/app/Database.php';
require_once ROOT.'/app/Domain.php';
require_once ROOT.'/app/RecordDeletion.php';
require_once ROOT.'/app/helpers.php';
if (PHP_SAPI !== 'cli' || defined('HTTP_TEST')) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.save_path', ROOT.'/storage/sessions');
    session_name(getenv('APP_ENV') === 'testing' ? 'faeyza_test' : 'faeyza_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>getenv('SESSION_SECURE')==='true' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'),'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');
    if (isset($_SESSION['last_seen']) && time()-$_SESSION['last_seen'] > 7200) $_SESSION = [];
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
