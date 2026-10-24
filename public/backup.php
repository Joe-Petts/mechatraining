<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
start_session();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if(!isset($_SESSION['uid'])||($_SESSION['role']??'')!=='admin'){http_response_code(403);exit('Nur Administratoren.');}
$csrf=(string)($_SESSION['csrf']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,(string)($_POST['csrf']??''))){http_response_code(403);exit('Ungültige Anfrage.');}
    try{
        require dirname(__DIR__).'/backup.php';
        $result=create_project_backup();
        $message='Backup erfolgreich erstellt: Backups/'.$result['folder'].' ('.$result['files'].' Dateien).';
    }catch(Throwable $e){http_response_code(500);$message='Backup fehlgeschlagen. Bitte Serverprotokoll prüfen.';error_log('MechaTraining backup: '.$e->getMessage());}
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MechaTraining Backup</title><style>body{font:16px system-ui;background:#edf2f5;color:#142332;max-width:650px;margin:10vh auto;padding:20px}main{background:white;padding:32px;border-radius:14px}button{padding:14px 20px;background:#142332;color:white;border:0;border-radius:8px;cursor:pointer}p{line-height:1.6}</style></head><body><main><h1>MechaTraining – Backup</h1><p>Erstellt eine Kopie des Projektverzeichnisses einschließlich einer konsistenten SQLite-Sicherung als ZIP-Datei <strong>Backups/TTMMJJJJ.zip</strong>. Vorhandene Sicherungen bleiben erhalten.</p><?php if(isset($message)):?><p role="status"><?=htmlspecialchars($message,ENT_QUOTES,'UTF-8')?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf,ENT_QUOTES,'UTF-8')?>"><button type="submit">Backup jetzt erstellen</button></form><p><a href="./">Zurück zu MechaTraining</a></p></main></body></html>
