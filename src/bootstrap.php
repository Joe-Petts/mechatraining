<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!extension_loaded('pdo_sqlite')) throw new RuntimeException('PDO SQLite ist nicht verfügbar.');
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    $pdo = new PDO('sqlite:' . $dir . '/mechatraining.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    return $pdo;
}
function migrate(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'student', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS progress (user_id INTEGER NOT NULL, question_id TEXT NOT NULL, correct INTEGER NOT NULL, attempts INTEGER NOT NULL DEFAULT 1, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id, question_id), FOREIGN KEY(user_id) REFERENCES users(id))");
}
function migrate_notes(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS notes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_notes_user ON notes(user_id, updated_at DESC)");
}
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
    session_start();
}
function json_response(array $payload, int $status=200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store'); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit;
}
