<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/catalog.php';
start_session();
try { $pdo=db(); migrate($pdo); }
catch(Throwable $e) { json_response(['error'=>'Datenbank nicht verfügbar.'],503); }
$action=$_GET['action'] ?? '';
if ($action==='session') json_response(['user'=>isset($_SESSION['uid'])?['name'=>$_SESSION['name'],'role'=>$_SESSION['role']]:null,'csrf'=>$_SESSION['csrf']??null]);
if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $stmt=$pdo->prepare('SELECT * FROM users WHERE username=?');
    $stmt->execute([(string)($data['username']??'')]); $user=$stmt->fetch();
    if (!$user || !password_verify((string)($data['password']??''),$user['password_hash'])) json_response(['error'=>'Anmeldung fehlgeschlagen.'],401);
    session_regenerate_id(true);
    $_SESSION=['uid'=>(int)$user['id'],'name'=>$user['username'],'role'=>$user['role'],'csrf'=>bin2hex(random_bytes(32))];
    json_response(['user'=>['name'=>$user['username'],'role'=>$user['role']],'csrf'=>$_SESSION['csrf']]);
}
if (!isset($_SESSION['uid'])) json_response(['error'=>'Nicht angemeldet.'],401);
if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??'')) json_response(['error'=>'Ungültiges CSRF-Token.'],403);
if ($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST') { $_SESSION=[]; session_destroy(); json_response(['ok'=>true]); }
if ($action==='change_password' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $pass=$data['password']??null;
    if (!is_string($pass) || strlen($pass)<4 || strlen($pass)>256) json_response(['error'=>'Passwort muss 4 bis 256 Zeichen enthalten.'],422);
    $stmt=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
    $stmt->execute([password_hash($pass,PASSWORD_DEFAULT),(int)$_SESSION['uid']]);
    session_regenerate_id(true);
    $_SESSION['csrf']=bin2hex(random_bytes(32));
    json_response(['ok'=>true,'csrf'=>$_SESSION['csrf']]);
}
if ($action==='users' && $_SERVER['REQUEST_METHOD']==='GET') {
    if ($_SESSION['role']!=='admin') json_response(['error'=>'Keine Berechtigung.'],403);
    json_response(['users'=>$pdo->query('SELECT id,username,role,created_at FROM users ORDER BY id')->fetchAll()]);
}
if ($action==='create_user' && $_SERVER['REQUEST_METHOD']==='POST') {
    if ($_SESSION['role']!=='admin') json_response(['error'=>'Keine Berechtigung.'],403);
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $name=trim((string)($data['username']??'')); $pass=(string)($data['password']??'');
    if (!preg_match('/^[a-zA-Z0-9_-]{3,32}$/',$name) || strlen($pass)<12 || strlen($pass)>256) json_response(['error'=>'Benutzername 3–32 Zeichen; Passwort mindestens 12 Zeichen.'],422);
    try { $stmt=$pdo->prepare("INSERT INTO users(username,password_hash,role) VALUES(?,?,'student')");$stmt->execute([$name,password_hash($pass,PASSWORD_DEFAULT)]); }
    catch(PDOException $e) { json_response(['error'=>'Benutzername bereits vergeben oder Datenbankfehler.'],409); }
    json_response(['ok'=>true],201);
}
if (in_array($action,['notes_list','notes_save','notes_delete'],true)) {
    try { migrate_notes($pdo); } catch(Throwable $e) { json_response(['error'=>'Notizspeicher nicht verfügbar.'],503); }
    $uid=(int)$_SESSION['uid'];
    if ($action==='notes_list' && $_SERVER['REQUEST_METHOD']==='GET') {
        $stmt=$pdo->prepare('SELECT id,title,body,created_at,updated_at FROM notes WHERE user_id=? ORDER BY updated_at DESC,id DESC');
        $stmt->execute([$uid]);json_response(['notes'=>$stmt->fetchAll()]);
    }
    if ($action==='notes_save' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data=json_decode(file_get_contents('php://input'),true) ?: [];
        $id=$data['id']??null;$title=trim((string)($data['title']??''));$body=(string)($data['body']??'');
        if ($title==='' || mb_strlen($title)>120 || mb_strlen($body)>50000) json_response(['error'=>'Titel erforderlich (max. 120 Zeichen), Notiz maximal 50.000 Zeichen.'],422);
        if ($id===null) {
            $stmt=$pdo->prepare('INSERT INTO notes(user_id,title,body) VALUES(?,?,?)');$stmt->execute([$uid,$title,$body]);
            json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()],201);
        }
        if (!is_int($id) || $id<1) json_response(['error'=>'Ungültige Notiz.'],422);
        $stmt=$pdo->prepare('UPDATE notes SET title=?,body=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?');$stmt->execute([$title,$body,$id,$uid]);
        if (!$stmt->rowCount()) { $check=$pdo->prepare('SELECT 1 FROM notes WHERE id=? AND user_id=?');$check->execute([$id,$uid]);if(!$check->fetchColumn())json_response(['error'=>'Notiz nicht gefunden.'],404); }
        json_response(['ok'=>true,'id'=>$id]);
    }
    if ($action==='notes_delete' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data=json_decode(file_get_contents('php://input'),true) ?: [];$id=$data['id']??null;
        if (!is_int($id)||$id<1)json_response(['error'=>'Ungültige Notiz.'],422);
        $stmt=$pdo->prepare('DELETE FROM notes WHERE id=? AND user_id=?');$stmt->execute([$id,$uid]);json_response(['ok'=>true]);
    }
    json_response(['error'=>'Methode nicht erlaubt.'],405);
}

if (in_array($action,['futter_list','futter_status','futter_public','futter_delete_block'],true)) {
 migrate_futter($pdo);
 $files=['elektrotechnik.json','pneumatik.json','metallbau.json'];$catalog=[];
 foreach($files as $file) {
  $set=json_decode(file_get_contents(dirname(__DIR__).'/content/ap1/'.$file),true);
  foreach(($set['modules']??[$set]) as $module) foreach($module['questions'] as $q) {
   $q['topic']=$set['title'];$q['module']=$module['title'];$q['batch_id']=$q['batch_id']??($file==='elektrotechnik.json'?'elektro-bestand':(preg_match('/^ap1-pneu-(symbole|anschluesse|schaltplaene|kennzeichnung)-/',$q['id'])?'pneu-zylindersteuerung':'pneu-grundlagen'));$catalog[$q['id']]=$q;
  }
 }
 $statuses=$pdo->query('SELECT question_id,status FROM futter_status')->fetchAll(PDO::FETCH_KEY_PAIR);
 $deletedBatches=$pdo->query('SELECT batch_id FROM futter_deleted_batches')->fetchAll(PDO::FETCH_COLUMN);
 if ($action==='futter_public' && $_SERVER['REQUEST_METHOD']==='GET') {
  $hidden=array_keys(array_filter($statuses,fn($v)=>$v!=='active'));foreach($catalog as $q)if(in_array($q['batch_id'],$deletedBatches,true))$hidden[]=$q['id'];json_response(['hidden'=>array_values(array_unique($hidden))]);
 }
 if ($_SESSION['role']!=='admin') json_response(['error'=>'Keine Berechtigung.'],403);
 if ($action==='futter_list' && $_SERVER['REQUEST_METHOD']==='GET') {
  $items=[];foreach($catalog as $id=>$q) {if(in_array($q['batch_id'],$deletedBatches,true))continue;
   $q['status']=$statuses[$id]??'active';$items[]=$q;
  }
  $events=$pdo->query('SELECT question_id,action,changed_at FROM futter_events ORDER BY id DESC LIMIT 100')->fetchAll();
  json_response(['items'=>$items,'events'=>$events]);
 }
 if ($action==='futter_delete_block' && $_SERVER['REQUEST_METHOD']==='POST') {
  $data=json_decode(file_get_contents('php://input'),true)?:[];$batch=$data['batch_id']??null;
  if(!is_string($batch)||$batch===''||in_array($batch,$deletedBatches,true))json_response(['error'=>'Ungültiger Block.'],422);
  $ids=[];foreach($catalog as $q)if($q['batch_id']===$batch)$ids[]=$q['id'];
  if(!$ids)json_response(['error'=>'Block nicht gefunden.'],404);
  try{$pdo->beginTransaction();$stmt=$pdo->prepare("INSERT INTO futter_status(question_id,status) VALUES(?,'archived') ON CONFLICT(question_id) DO UPDATE SET status='archived',updated_at=CURRENT_TIMESTAMP");$event=$pdo->prepare("INSERT INTO futter_events(question_id,action) VALUES(?,'deleted_block')");foreach($ids as $id){$stmt->execute([$id]);$event->execute([$id]);}$pdo->prepare('INSERT INTO futter_deleted_batches(batch_id) VALUES(?)')->execute([$batch]);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();json_response(['error'=>'Block konnte nicht entfernt werden.'],500);}
  json_response(['ok'=>true,'count'=>count($ids)]);
 }
 if ($action==='futter_status' && $_SERVER['REQUEST_METHOD']==='POST') {
  $data=json_decode(file_get_contents('php://input'),true)?:[];
  $id=$data['question_id']??null;$status=$data['status']??null;
  if(!is_string($id)||!isset($catalog[$id])||!in_array($status,['active','disabled','archived'],true))json_response(['error'=>'Ungültiger Status.'],422);
  $pdo->beginTransaction();
  try {
   $stmt=$pdo->prepare('INSERT INTO futter_status(question_id,status) VALUES(?,?) ON CONFLICT(question_id) DO UPDATE SET status=excluded.status,updated_at=CURRENT_TIMESTAMP');$stmt->execute([$id,$status]);
   $stmt=$pdo->prepare('INSERT INTO futter_events(question_id,action) VALUES(?,?)');$stmt->execute([$id,$status]);
   $pdo->commit();
  }catch(Throwable $e){$pdo->rollBack();json_response(['error'=>'Speichern fehlgeschlagen.'],500);}
  json_response(['ok'=>true]);
 }
 json_response(['error'=>'Methode nicht erlaubt.'],405);
}
if ($action==='progress' && $_SERVER['REQUEST_METHOD']==='GET') {
    $stmt=$pdo->prepare('SELECT question_id,correct,attempts FROM progress WHERE user_id=?');
    $stmt->execute([$_SESSION['uid']]); $rows=$stmt->fetchAll(); $count=0; foreach($rows as $row) if ((int)$row['correct']===1) $count++; json_response(['progress'=>$rows,'xp'=>$count*10]);
}
if ($action==='answer' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $questionSets=[dirname(__DIR__).'/content/ap1/elektrotechnik.json',dirname(__DIR__).'/content/ap1/pneumatik.json',dirname(__DIR__).'/content/ap1/metallbau.json'];
    $found=null; foreach($questionSets as $path) { $set=json_decode(file_get_contents($path),true); $modules=isset($set['modules'])?$set['modules']:[$set]; foreach($modules as $module) foreach($module['questions'] as $q) if($q['id']===($data['question_id']??'')) {$found=$q;break 3;} }
    if($found){migrate_futter($pdo);$check=$pdo->prepare('SELECT status FROM futter_status WHERE question_id=?');$check->execute([$found['id']]);if(($check->fetchColumn()?:'active')!=='active')json_response(['error'=>'Aufgabe nicht aktiv.'],403);}
    if(!$found || !is_int($data['answer']??null) || !array_key_exists($data['answer'],$found['options'])) json_response(['error'=>'Ungültige Antwort.'],422);
    $correct=(int)($data['answer']===$found['correct']);
    $stmt=$pdo->prepare('INSERT INTO progress(user_id,question_id,correct,attempts) VALUES(?,?,?,1) ON CONFLICT(user_id,question_id) DO UPDATE SET correct=MAX(correct,excluded.correct),attempts=attempts+1,updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([$_SESSION['uid'],$found['id'],$correct]);
    json_response(['correct'=>(bool)$correct,'correct_index'=>$found['correct'],'explanation'=>$found['explanation']]);
}
json_response(['error'=>'Unbekannte Aktion.'],404);
