<?php
declare(strict_types=1);
/**
 * MechaTraining full project backup.
 * Located outside the webroot. Invoke only from an authenticated admin endpoint
 * or from CLI: php backup.php
 */
function create_project_backup(): array {
    $root=__DIR__;
    $backupRoot=$root.'/Backups';
    if(!is_dir($backupRoot)&&!mkdir($backupRoot,0700,true)&&!is_dir($backupRoot))throw new RuntimeException('Backup-Ordner konnte nicht erstellt werden.');
    $stamp=date('dmY');
    $target=$backupRoot.'/'.$stamp;
    if(is_dir($target))$target.='-'.date('His');
    if(!mkdir($target,0700))throw new RuntimeException('Backup-Ziel konnte nicht erstellt werden.');
    $count=0;
    try {
        if(!class_exists('ZipArchive'))throw new RuntimeException('PHP-Zip-Erweiterung fehlt.');
        $archivePath=$backupRoot.'/'.basename($target).'.zip';
        $zip=new ZipArchive();
        if($zip->open($archivePath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP konnte nicht angelegt werden.');
        $dbFile=$root.'/storage/mechatraining.sqlite';
        try {
            if(is_file($dbFile)){
                $db=new SQLite3($dbFile,SQLITE3_OPEN_READONLY);
                $snapshot=new SQLite3($target.'/database.sqlite',SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE);
                try {if(!$db->backup($snapshot))throw new RuntimeException('SQLite-Sicherung fehlgeschlagen.');}
                finally {$snapshot->close();$db->close();}
                if(!$zip->addFile($target.'/database.sqlite','storage/mechatraining.sqlite'))throw new RuntimeException('SQLite konnte nicht archiviert werden.');
                $count++;
            }
            $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
            foreach($files as $file){
                if(!$file->isFile()||$file->isLink())continue;
                $relative=substr($file->getPathname(),strlen($root)+1);
                if(preg_match('~^(Backups|\\.git|\\.deploy-private)/~',$relative))continue;
                if(preg_match('~^storage/mechatraining\\.sqlite(?:-(?:wal|shm|journal))?$~',$relative))continue;
                if(!$zip->addFile($file->getPathname(),$relative))throw new RuntimeException('Datei konnte nicht archiviert werden: '.$relative);
                $count++;
            }
            $zip->addFromString('BACKUP_INFO.txt','MechaTraining '.date(DATE_ATOM).' / Dateien: '.$count);
            if(!$zip->close())throw new RuntimeException('ZIP konnte nicht abgeschlossen werden.');
        }catch(Throwable $e){$zip->close();@unlink($archivePath);throw $e;}
        @unlink($target.'/database.sqlite');@rmdir($target);
        return ['folder'=>basename($archivePath),'files'=>$count];
    }catch(Throwable $e){
        // Keep incomplete backups recognizable; never silently claim success.
        file_put_contents($target.'/BACKUP_FAILED.txt',$e->getMessage());
        throw $e;
    }
}
if(PHP_SAPI==='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    try{$result=create_project_backup();echo 'Backup erstellt: Backups/'.$result['folder'].' ('.$result['files']." Dateien)\n";}
    catch(Throwable $e){fwrite(STDERR,'Backup fehlgeschlagen: '.$e->getMessage()."\n");exit(1);}
}
