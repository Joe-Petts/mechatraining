<?php
declare(strict_types=1);

/** Additive learning-content schema. Existing users and progress remain untouched. */
function migrate_catalog(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS subjects (id TEXT PRIMARY KEY, title TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','disabled','archived')))");
    $db->exec("CREATE TABLE IF NOT EXISTS learning_modules (id TEXT PRIMARY KEY, subject_id TEXT NOT NULL REFERENCES subjects(id), title TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','disabled','archived')))");
    $db->exec("CREATE TABLE IF NOT EXISTS learning_sources (id TEXT PRIMARY KEY, title TEXT NOT NULL, origin TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS learning_questions (id TEXT PRIMARY KEY, module_id TEXT NOT NULL REFERENCES learning_modules(id), source_id TEXT REFERENCES learning_sources(id), type TEXT NOT NULL DEFAULT 'multiple_choice', title TEXT NOT NULL DEFAULT '', prompt TEXT NOT NULL, difficulty INTEGER NOT NULL DEFAULT 2 CHECK(difficulty BETWEEN 1 AND 5), exam_part TEXT NOT NULL DEFAULT 'AP1', solution_explanation TEXT NOT NULL DEFAULT '', solution_data TEXT NOT NULL DEFAULT '{}', config_json TEXT NOT NULL DEFAULT '{}', status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('draft','active','disabled','archived')), version INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS question_options (question_id TEXT NOT NULL REFERENCES learning_questions(id), option_index INTEGER NOT NULL, text TEXT NOT NULL, is_correct INTEGER NOT NULL DEFAULT 0, PRIMARY KEY(question_id,option_index))");
    $db->exec("CREATE TABLE IF NOT EXISTS question_assets (id INTEGER PRIMARY KEY AUTOINCREMENT, question_id TEXT NOT NULL REFERENCES learning_questions(id), asset_path TEXT NOT NULL, kind TEXT NOT NULL DEFAULT 'image', alt_text TEXT NOT NULL DEFAULT '')");
    $db->exec("CREATE TABLE IF NOT EXISTS question_revisions (id INTEGER PRIMARY KEY AUTOINCREMENT, question_id TEXT NOT NULL REFERENCES learning_questions(id), version INTEGER NOT NULL, snapshot_json TEXT NOT NULL, changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS question_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id), question_id TEXT NOT NULL, answer_json TEXT NOT NULL, is_correct INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS question_tags (question_id TEXT NOT NULL REFERENCES learning_questions(id), tag TEXT NOT NULL, PRIMARY KEY(question_id,tag))");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_questions_module_status ON learning_questions(module_id,status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_modules_subject ON learning_modules(subject_id,status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_question_attempts_user ON question_attempts(user_id,created_at)");
}

/** Idempotent initial import: existing records are never overwritten or resurrected. */
function import_legacy_catalog(PDO $db): void {
    migrate_catalog($db);
    $files=['elektrotechnik.json'=>'elektro','metallbau.json'=>'metall','pneumatik.json'=>'pneu'];
    $db->beginTransaction();
    try {
        $subject=$db->prepare('INSERT OR IGNORE INTO subjects(id,title,sort_order) VALUES(?,?,?)');
        $module=$db->prepare('INSERT OR IGNORE INTO learning_modules(id,subject_id,title,sort_order) VALUES(?,?,?,?)');
        $source=$db->prepare('INSERT OR IGNORE INTO learning_sources(id,title,origin) VALUES(?,?,?)');
        $question=$db->prepare('INSERT OR IGNORE INTO learning_questions(id,module_id,source_id,prompt,solution_explanation,solution_data,config_json,created_at,status) VALUES(?,?,?,?,?,?,?,?,?)');
        $option=$db->prepare('INSERT OR IGNORE INTO question_options(question_id,option_index,text,is_correct) VALUES(?,?,?,?)');
        foreach($files as $file=>$subjectId) {
            $path=dirname(__DIR__).'/content/ap1/'.$file;
            if(!is_file($path))continue;
            $set=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
            $subject->execute([$subjectId,$set['title'],0]);
            foreach(($set['modules']??[$set]) as $position=>$m) {
                $moduleId=$subjectId==='elektro'?'electro':(string)$m['id'];
                // Module IDs in the legacy JSON are only unique within a subject.
                $storageId=$subjectId.':'.$moduleId;
                $module->execute([$storageId,$subjectId,$m['title'],$position]);
                foreach($m['questions'] as $q) {
                    $batch=(string)($q['batch_id']??($q['source']??'legacy-'.$subjectId));
                    $source->execute([$batch,$batch,(string)($q['source']??$file)]);
                    $question->execute([$q['id'],$storageId,$batch,$q['prompt'],$q['explanation']??'',json_encode(['correct'=>$q['correct']],JSON_UNESCAPED_UNICODE),json_encode(['diagram'=>$q['diagram']??null],JSON_UNESCAPED_UNICODE),$q['created_at']??gmdate('Y-m-d H:i:s'),'active']);
                    foreach($q['options'] as $i=>$answer)$option->execute([$q['id'],$i,(string)$answer,(int)($i===$q['correct'])]);
                }
            }
        }
        $db->commit();
    } catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
}

function catalog_modules(PDO $db): array {
    $rows=$db->query("SELECT q.id,q.module_id,q.prompt,q.solution_explanation,q.config_json,m.title AS module_title,m.subject_id,s.title AS subject_title,m.sort_order FROM learning_questions q JOIN learning_modules m ON m.id=q.module_id JOIN subjects s ON s.id=m.subject_id WHERE q.status='active' AND m.status='active' AND s.status='active' ORDER BY s.sort_order,m.sort_order,q.id")->fetchAll();
    $options=$db->query('SELECT question_id,option_index,text,is_correct FROM question_options ORDER BY question_id,option_index')->fetchAll();
    $byQuestion=[];foreach($options as $o)$byQuestion[$o['question_id']][]=$o;
    $out=[];foreach($rows as $r) {
        $qopts=$byQuestion[$r['id']]??[];$correct=0;$texts=[];
        foreach($qopts as $o){$texts[]=$o['text'];if((int)$o['is_correct']===1)$correct=(int)$o['option_index'];}
        $config=json_decode($r['config_json'],true)?:[];
        $q=['id'=>$r['id'],'prompt'=>$r['prompt'],'options'=>$texts,'correct'=>$correct,'explanation'=>$r['solution_explanation']];
        if(!empty($config['diagram']))$q['diagram']=$config['diagram'];
        $id=$r['module_id'];if(!isset($out[$id]))$out[$id]=['id'=>str_contains($id,':')?explode(':',$id,2)[1]:$id,'title'=>$r['module_title'],'subject'=>$r['subject_id'],'questions'=>[]];
        $out[$id]['questions'][]=$q;
    }
    return array_values($out);
}

/** One-time, transactional content reset. Never touches users, notes or passwords. */
function reset_learning_content_once(PDO $db): void {
    migrate_catalog($db);
    $db->exec("CREATE TABLE IF NOT EXISTS learning_system_state (name TEXT PRIMARY KEY, value TEXT NOT NULL)");
    $done=$db->query("SELECT value FROM learning_system_state WHERE name='clean_reset_20261020'")->fetchColumn();
    if($done!==false)return;
    $db->beginTransaction();
    try {
        foreach(['question_attempts','question_tags','question_revisions','question_assets','question_options','learning_questions','learning_modules','subjects','learning_sources','progress','futter_status','futter_events','futter_deleted_batches'] as $table) {
            $db->exec("DELETE FROM ".$table);
        }
        $db->exec("INSERT INTO learning_system_state(name,value) VALUES('clean_reset_20261020',CURRENT_TIMESTAMP)");
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
