<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
start_session();
try { $pdo=db(); migrate($pdo); }
catch(Throwable $e) { json_response(['error'=>'Serverdatenbank nicht verfügbar.'],503); }
$action=$_GET['action'] ?? '';
if ($action==='session') {
    json_response(['user'=>isset($_SESSION['uid'])?['name'=>$_SESSION['name'],'role'=>$_SESSION['role']]:null,'csrf'=>$_SESSION['csrf']??null]);
}
if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $stmt=$pdo->prepare('SELECT * FROM users WHERE username=?'); $stmt->execute([$data['username']??'']);
    $user=$stmt->fetch();
    if (!$user || !password_verify((string)($data['password']??''),$user['password_hash'])) json_response(['error'=>'Anmeldung fehlgeschlagen.'],401);
    session_regenerate_id(true); $_SESSION=['uid'=>(int)$user['id'],'name'=>$user['username'],'role'=>$user['role'],'csrf'=>bin2hex(random_bytes(32))];
    json_response(['user'=>['name'=>$user['username'],'role'=>$user['role']],'csrf'=>$_SESSION['csrf']]);
}
if (!isset($_SESSION['uid'])) json_response(['error'=>'Nicht angemeldet.'],401);
if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??'')) json_response(['error'=>'Ungültiges CSRF-Token.'],403);
if ($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST') { $_SESSION=[]; session_destroy(); json_response(['ok'=>true]); }
if ($action==='progress' && $_SERVER['REQUEST_METHOD']==='GET') {
    $stmt=$pdo->prepare('SELECT question_id,correct,attempts FROM progress WHERE user_id=?'); $stmt->execute([$_SESSION['uid']]); json_response(['progress'=>$stmt->fetchAll()]);
}
if ($action==='answer' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $questions=json_decode(file_get_contents(dirname(__DIR__).'/content/ap1/elektrotechnik.json'),true);
    $found=null; foreach($questions['questions'] as $q) if($q['id']===($data['question_id']??'')) {$found=$q;break;}
    if(!$found || !is_int($data['answer']??null) || !array_key_exists($data['answer'],$found['options'])) json_response(['error'=>'Ungültige Antwort.'],422);
    $correct=(int)($data['answer']===$found['correct']);
    $stmt=$pdo->prepare('INSERT INTO progress(user_id,question_id,correct,attempts) VALUES(?,?,?,1) ON CONFLICT(user_id,question_id) DO UPDATE SET correct=MAX(correct,excluded.correct), attempts=attempts+1,updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([$_SESSION['uid'],$found['id'],$correct]);
    json_response(['correct'=>(bool)$correct,'correct_index'=>$found['correct'],'explanation'=>$found['explanation']]);
}
json_response(['error'=>'Unbekannte Aktion.'],404);
