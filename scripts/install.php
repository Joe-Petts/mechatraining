<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Nur CLI'); }
if ($argc < 3) { fwrite(STDERR, "Aufruf: php scripts/install.php BENUTZERNAME PASSWORT\n"); exit(1); }
[$script,$username,$password] = $argv;
if (!preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $username) || strlen($password) < 12) { fwrite(STDERR, "Benutzername 3-32 Zeichen, Passwort mindestens 12 Zeichen.\n"); exit(1); }
$pdo = db(); migrate($pdo);
$stmt=$pdo->prepare("INSERT INTO users(username,password_hash,role) VALUES(?,?,'admin')");
try { $stmt->execute([$username,password_hash($password,PASSWORD_DEFAULT)]); echo "Admin angelegt.\n"; }
catch(PDOException $e) { fwrite(STDERR,"Admin konnte nicht angelegt werden (Benutzer existiert möglicherweise).\n"); exit(1); }
