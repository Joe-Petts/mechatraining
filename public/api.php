<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
start_session();
$action=$_GET['action'] ?? '';
if ($action==='session') json_response(['user'=>!empty($_SESSION['demo'])?['name'=>'Demo','role'=>'demo']:null,'csrf'=>$_SESSION['csrf']??null]);
if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    // Temporary prototype gate only. Not suitable for personal data or production access.
    if (!hash_equals('2404',(string)($data['password']??''))) json_response(['error'=>'Falscher Zugangscode.'],401);
    session_regenerate_id(true);
    $_SESSION=['demo'=>true,'csrf'=>bin2hex(random_bytes(32))];
    json_response(['user'=>['name'=>'Demo','role'=>'demo'],'csrf'=>$_SESSION['csrf']]);
}
if (empty($_SESSION['demo'])) json_response(['error'=>'Nicht angemeldet.'],401);
if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??'')) json_response(['error'=>'Ungültiges CSRF-Token.'],403);
if ($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST') { $_SESSION=[]; session_destroy(); json_response(['ok'=>true]); }
if ($action==='progress' && $_SERVER['REQUEST_METHOD']==='GET') json_response(['progress'=>[]]);
if ($action==='answer' && $_SERVER['REQUEST_METHOD']==='POST') {
    $data=json_decode(file_get_contents('php://input'),true) ?: [];
    $questions=json_decode(file_get_contents(dirname(__DIR__).'/content/ap1/elektrotechnik.json'),true);
    $found=null; foreach($questions['questions'] as $q) if($q['id']===($data['question_id']??'')) {$found=$q;break;}
    if(!$found || !is_int($data['answer']??null) || !array_key_exists($data['answer'],$found['options'])) json_response(['error'=>'Ungültige Antwort.'],422);
    $correct=$data['answer']===$found['correct'];
    json_response(['correct'=>$correct,'correct_index'=>$found['correct'],'explanation'=>$found['explanation']]);
}
json_response(['error'=>'Unbekannte Aktion.'],404);
