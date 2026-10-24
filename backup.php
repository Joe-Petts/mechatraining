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
        $dbFile=$root.'/storage/mechatraining.sqlite';
        $dbRelative='storage/mechatraining.sqlite';
        if(is_file($dbFile)){
            if(!class_exists('SQLite3'))throw new RuntimeException('SQLite3-Erweiterung für konsistente Sicherung fehlt.');
            $db=new SQLite3($dbFile,SQLITE3_OPEN_READONLY);
            $destDir=$target.'/storage';mkdir($destDir,0700,true);
            $snapshot=$db->backup($destDir.'/mechatraining.sqlite');
            $db->close();
            if(!$snapshot)throw new RuntimeException('SQLite-Backup fehlgeschlagen.');
            $count++;
        }
        $iterator=new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach($iterator as $entry){
            $source=$entry->getPathname();
            $relative=substr($source,strlen($root)+1);
            $parts=explode(DIRECTORY_SEPARATOR,$relative);
            if(in_array($parts[0],['Backups','.git','.deploy-private'],true))continue;
            if($entry->isLink())continue;
            if($entry->isDir())continue;
            if($relative===$dbRelative||preg_match('~^storage/mechatraining\\.sqlite-(?:wal|shm|journal)$~',$relative))continue;
            $dest=$target.'/'.$relative;
            $parent=dirname($dest);
            if(!is_dir($parent)&&!mkdir($parent,0700,true)&&!is_dir($parent))throw new RuntimeException('Backup-Verzeichnis konnte nicht erstellt werden.');
            if(!copy($source,$dest))throw new RuntimeException('Kopieren fehlgeschlagen: '.$relative);
            $count++;
        }
        file_put_contents($target.'/BACKUP_INFO.txt',"MechaTraining Backup\nCreated: ".date(DATE_ATOM)."\nFiles: ".$count."\n");
        return ['folder'=>basename($target),'files'=>$count];
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
