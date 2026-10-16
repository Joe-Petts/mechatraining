<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
start_session();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$notice = '';
$installed = false;
try {
    $pdo = db();
    migrate($pdo);
    $installed = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (Throwable $e) {
    http_response_code(503);
    $notice = 'Datenbank nicht verfügbar. Bitte Hosting-Konfiguration prüfen.';
}
if (!$installed && isset($pdo) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyFile = dirname(__DIR__) . '/storage/setup.key';
    $submitted = (string)($_POST['setup_key'] ?? '');
    $actual = is_file($keyFile) ? trim((string)file_get_contents($keyFile)) : '';
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!hash_equals($_SESSION['setup_csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        $notice = 'Sitzung abgelaufen. Bitte Seite neu laden.';
    } elseif (strlen($actual) < 32 || !hash_equals($actual, $submitted)) {
        $notice = 'Einrichtungsschlüssel ungültig oder noch nicht auf dem Server vorhanden.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $username) || strlen($password) < 12 || strlen($password) > 256) {
        $notice = 'Benutzername: 3–32 Zeichen (Buchstaben, Zahlen, _ oder -). Passwort: 12–256 Zeichen.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users(username,password_hash,role) VALUES(?,?,'admin')");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            @unlink($keyFile);
            $installed = true;
            $notice = 'Administrator angelegt. Du kannst dich jetzt anmelden.';
        } catch (Throwable $e) {
            $notice = 'Einrichtung fehlgeschlagen. Bitte erneut versuchen.';
        }
    }
}
$_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>MechaTraining einrichten</title><link rel="stylesheet" href="style.css"></head><body><main><section class="intro"><p class="eyebrow">MECHATRAINING · ERSTEINRICHTUNG</p><h1>Administrator <span class="accent">einrichten</span></h1></section><section class="panel">
<?php if ($installed): ?><h2>Einrichtung abgeschlossen</h2><p><?=h($notice ?: 'Es existiert bereits ein Benutzerkonto. Der Einrichtungsassistent ist gesperrt.')?></p><p><a href="/">Zur Anmeldung</a></p>
<?php else: ?><h2>Einmalige Einrichtung</h2><p>Den Einrichtungsschlüssel hinterlegst du als GitHub Actions Secret <code>SETUP_KEY</code>. Er wird nicht im Repository gespeichert.</p>
<?php if ($notice): ?><p role="alert"><?=h($notice)?></p><?php endif; ?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($_SESSION['setup_csrf'])?>">
<label>Einrichtungsschlüssel<input type="password" name="setup_key" required minlength="32"></label>
<label>Administrator-Benutzername<input name="username" required minlength="3" maxlength="32" pattern="[a-zA-Z0-9_-]+"></label>
<label>Neues Passwort<input type="password" name="password" required minlength="12" maxlength="256" autocomplete="new-password"></label>
<button type="submit">Administrator erstellen</button></form>
<?php endif; ?></section></main></body></html>
