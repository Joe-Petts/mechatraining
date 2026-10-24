<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/catalog.php';
start_session();
try { $pdo=db(); migrate($pdo); }
catch(Throwable $e) { json_response(['error'=>'Datenbank nicht verfügbar.'],503); }
reset_learning_content_once($pdo);import_pending_learning($pdo);
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
 if ($action==='futter_public' && $_SERVER['REQUEST_METHOD']==='GET') {
  $hidden=$pdo->query("SELECT id FROM learning_questions WHERE status<>'active'")->fetchAll(PDO::FETCH_COLUMN);
  json_response(['hidden'=>$hidden]);
 }
 if ($_SESSION['role']!=='admin') json_response(['error'=>'Keine Berechtigung.'],403);
 if ($action==='futter_list' && $_SERVER['REQUEST_METHOD']==='GET') {
  $items=$pdo->query("SELECT q.id,q.prompt,q.status,q.created_at,q.source_id AS batch_id,m.title AS module,s.title AS topic,q.solution_explanation AS explanation FROM learning_questions q JOIN learning_modules m ON m.id=q.module_id JOIN subjects s ON s.id=m.subject_id ORDER BY q.created_at DESC,q.id")->fetchAll();
  $events=$pdo->query('SELECT question_id,action,changed_at FROM futter_events ORDER BY id DESC LIMIT 100')->fetchAll();
  json_response(['items'=>$items,'events'=>$events]);
 }
 if ($action==='futter_status' && $_SERVER['REQUEST_METHOD']==='POST') {
  $data=json_decode(file_get_contents('php://input'),true)?:[];
  $id=$data['question_id']??null;$status=$data['status']??null;
  if(!is_string($id)||!in_array($status,['active','disabled','archived'],true))json_response(['error'=>'Ungültiger Status.'],422);
  $stmt=$pdo->prepare('UPDATE learning_questions SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
  $stmt->execute([$status,$id]);
  if(!$stmt->rowCount())json_response(['error'=>'Aufgabe nicht gefunden.'],404);
  $pdo->prepare('INSERT INTO futter_events(question_id,action) VALUES(?,?)')->execute([$id,$status]);
  json_response(['ok'=>true]);
 }
 if ($action==='futter_delete_block' && $_SERVER['REQUEST_METHOD']==='POST') {
  $data=json_decode(file_get_contents('php://input'),true)?:[];$batch=$data['batch_id']??null;
  if(!is_string($batch)||$batch==='')json_response(['error'=>'Ungültiger Block.'],422);
  $stmt=$pdo->prepare("UPDATE learning_questions SET status='archived',updated_at=CURRENT_TIMESTAMP WHERE source_id=? AND status<>'archived'");
  $stmt->execute([$batch]);if(!$stmt->rowCount())json_response(['error'=>'Block nicht gefunden oder bereits archiviert.'],404);
  json_response(['ok'=>true,'count'=>$stmt->rowCount()]);
 }
 json_response(['error'=>'Methode nicht erlaubt.'],405);
}
if ($action==='progress' && $_SERVER['REQUEST_METHOD']==='GET') {
    $stmt=$pdo->prepare('SELECT question_id,correct,attempts FROM progress WHERE user_id=?');
    $stmt->execute([$_SESSION['uid']]); $rows=$stmt->fetchAll(); $count=0; foreach($rows as $row) if ((int)$row['correct']===1) $count++; json_response(['progress'=>$rows,'xp'=>$count*10]);
}
if ($action==='answer' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $stmt=$pdo->prepare("SELECT id,type,status,solution_data,solution_explanation FROM learning_questions WHERE id=?");
    $stmt->execute([(string)($data['question_id']??'')]);
    $question=$stmt->fetch();
    if(!$question||$question['status']!=='active')json_response(['error'=>'Aufgabe nicht aktiv oder unbekannt.'],404);
    migrate_futter($pdo);
    $check=$pdo->prepare('SELECT status FROM futter_status WHERE question_id=?');
    $check->execute([$question['id']]);
    if(($check->fetchColumn()?:'active')!=='active')json_response(['error'=>'Aufgabe nicht aktiv.'],403);
    $solution=json_decode($question['solution_data'],true)?:[];
    $answer=$data['answer']??null;
    $correct=false;$correctIndex=null;
    switch($question['type']){
      case 'multiple_choice':
        $options=$pdo->prepare('SELECT option_index,is_correct FROM question_options WHERE question_id=? ORDER BY option_index');
        $options->execute([$question['id']]);$rows=$options->fetchAll();
        if(!is_int($answer)||!in_array($answer,array_map(static fn($r)=>(int)$r['option_index'],$rows),true))json_response(['error'=>'Ungültige Antwort.'],422);
        foreach($rows as $o)if((int)$o['is_correct']===1)$correctIndex=(int)$o['option_index'];
        $correct=$answer===$correctIndex;
        break;
      case 'numeric':
        if(!is_string($answer)&&!is_int($answer)&&!is_float($answer))json_response(['error'=>'Zahl erforderlich.'],422);
        $raw=str_replace(',','.',trim((string)$answer));
        if(!is_numeric($raw)||!is_finite((float)$raw)||!isset($solution['value']))json_response(['error'=>'Ungültiger Zahlenwert.'],422);
        $correct=abs((float)$raw-(float)$solution['value'])<=(float)($solution['tolerance']??0.001);
        break;
      case 'text_input':
        if(!is_string($answer)||mb_strlen($answer)>500)json_response(['error'=>'Textantwort erforderlich.'],422);
        $normalize=static fn($s)=>mb_strtolower(trim(preg_replace('/\\s+/u',' ',(string)$s)));
        $accepted=$solution['accepted']??[];
        $correct=in_array($normalize($answer),array_map($normalize,$accepted),true);
        break;
      default:json_response(['error'=>'Aufgabentyp noch nicht unterstützt.'],422);
    }
    $found=['id'=>$question['id'],'explanation'=>$question['solution_explanation']];
    $stmt=$pdo->prepare('INSERT INTO progress(user_id,question_id,correct,attempts) VALUES(?,?,?,1) ON CONFLICT(user_id,question_id) DO UPDATE SET correct=MAX(correct,excluded.correct),attempts=attempts+1,updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([$_SESSION['uid'],$found['id'],$correct]);
    json_response(['correct'=>(bool)$correct,'correct_index'=>$correctIndex,'explanation'=>$found['explanation']]);
}
json_response(['error'=>'Unbekannte Aktion.'],404);
