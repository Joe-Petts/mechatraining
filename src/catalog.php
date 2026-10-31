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
    $columns=$db->query("PRAGMA table_info(question_assets)")->fetchAll(PDO::FETCH_COLUMN,1);
    if(!in_array('placement',$columns,true))$db->exec("ALTER TABLE question_assets ADD COLUMN placement TEXT NOT NULL DEFAULT 'prompt'");
    if(!in_array('caption',$columns,true))$db->exec("ALTER TABLE question_assets ADD COLUMN caption TEXT NOT NULL DEFAULT ''");
    if(!in_array('sort_order',$columns,true))$db->exec("ALTER TABLE question_assets ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0");
    $db->exec("CREATE TABLE IF NOT EXISTS question_revisions (id INTEGER PRIMARY KEY AUTOINCREMENT, question_id TEXT NOT NULL REFERENCES learning_questions(id), version INTEGER NOT NULL, snapshot_json TEXT NOT NULL, changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS question_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id), question_id TEXT NOT NULL, answer_json TEXT NOT NULL, is_correct INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS question_tags (question_id TEXT NOT NULL REFERENCES learning_questions(id), tag TEXT NOT NULL, PRIMARY KEY(question_id,tag))");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_questions_module_status ON learning_questions(module_id,status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_learning_modules_subject ON learning_modules(subject_id,status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_question_attempts_user ON question_attempts(user_id,created_at)");
}

/** Idempotent initial import: existing records are never overwritten or resurrected. */
function import_legacy_catalog(PDO $db): void {
    return; // Legacy task files are retired.
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

/** Import only the approved drilling exercises into SQLite, without reviving old JSON content. */
function import_approved_drilling(PDO $db): void {
    migrate_catalog($db);
    $path=dirname(__DIR__).'/content/ap1/metallbau.json';
    if(!is_file($path))return;
    $data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    $batch='metall-bohren-berechnungen-20261022';
    $db->beginTransaction();
    try {
        $subject=$db->prepare('INSERT OR IGNORE INTO subjects(id,title,sort_order) VALUES(?,?,?)');
        $subject->execute(['metall','Metallbau',20]);
        $source=$db->prepare('INSERT OR IGNORE INTO learning_sources(id,title,origin) VALUES(?,?,?)');
        $source->execute([$batch,'Bohren – Rechenübungen','Arbeitsblätter Metallbau']);
        $module=$db->prepare('INSERT OR IGNORE INTO learning_modules(id,subject_id,title,sort_order) VALUES(?,?,?,?)');
        $question=$db->prepare('INSERT OR IGNORE INTO learning_questions(id,module_id,source_id,prompt,solution_explanation,solution_data,created_at,status) VALUES(?,?,?,?,?,?,?,?)');
        $option=$db->prepare('INSERT OR IGNORE INTO question_options(question_id,option_index,text,is_correct) VALUES(?,?,?,?)');
        foreach($data['modules']??[] as $position=>$m) {
            $approved=array_values(array_filter($m['questions']??[],static fn($q)=>($q['batch_id']??'')===$batch));
            if(!$approved)continue;
            $moduleId='metall:'.$m['id'];
            $module->execute([$moduleId,'metall',$m['title'],$position]);
            foreach($approved as $q) {
                $question->execute([$q['id'],$moduleId,$batch,$q['prompt'],$q['explanation']??'',json_encode(['correct'=>$q['correct']]),$q['created_at']??gmdate('c'),'active']);
                foreach($q['options'] as $i=>$answer)$option->execute([$q['id'],$i,$answer,(int)($i===$q['correct'])]);
            }
        }
        $db->commit();
    } catch(Throwable $e) {
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
}

/** Import a reviewed content batch; SQLite remains the runtime source. */
function import_learning_batch(PDO $db,array $batch): int {
    migrate_catalog($db);
    foreach(['id','subject','subject_title','modules'] as $field)if(empty($batch[$field]))throw new InvalidArgumentException('Importfeld fehlt: '.$field);
    $db->exec("CREATE TABLE IF NOT EXISTS learning_imports (id TEXT PRIMARY KEY, imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, count INTEGER NOT NULL)");
    $check=$db->prepare('SELECT 1 FROM learning_imports WHERE id=?');
    $check->execute([$batch['id']]);
    if($check->fetchColumn())return 0;
    $seen=[];
    foreach($batch['modules'] as $m)foreach($m['questions'] as $q) {
        $id=$q['id']??'';
        if(!is_string($id)||$id===''||isset($seen[$id])||empty($q['prompt'])||empty($q['type']))throw new InvalidArgumentException('Ungültige Aufgabe');
        $seen[$id]=true;
        if(!in_array($q['type'],['multiple_choice','numeric','text_input','matching','tool_identification'],true))throw new InvalidArgumentException('Unbekannter Aufgabentyp: '.$id);
        if($q['type']==='multiple_choice'&&(!isset($q['options'],$q['correct'])||count($q['options'])<2||!array_key_exists($q['correct'],$q['options'])))throw new InvalidArgumentException('Ungültige Auswahlfrage: '.$id);
    }
    $db->beginTransaction();
    try {
        $db->prepare('INSERT OR IGNORE INTO subjects(id,title) VALUES(?,?)')->execute([$batch['subject'],$batch['subject_title']]);
        $db->prepare('INSERT OR IGNORE INTO learning_sources(id,title,origin) VALUES(?,?,?)')->execute([$batch['id'],$batch['title']??$batch['id'],$batch['origin']??'']);
        $mod=$db->prepare('INSERT OR IGNORE INTO learning_modules(id,subject_id,title,sort_order) VALUES(?,?,?,?)');
        $qst=$db->prepare('INSERT OR IGNORE INTO learning_questions(id,module_id,source_id,type,prompt,difficulty,exam_part,solution_explanation,solution_data,config_json,status) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $opt=$db->prepare('INSERT OR IGNORE INTO question_options(question_id,option_index,text,is_correct) VALUES(?,?,?,?)');
        $asset=$db->prepare('INSERT INTO question_assets(question_id,asset_path,kind,alt_text,placement,caption,sort_order) VALUES(?,?,?,?,?,?,?)');
        $count=0;
        foreach($batch['modules'] as $position=>$m) {
            $moduleId=$batch['subject'].':'.$m['id'];
            $mod->execute([$moduleId,$batch['subject'],$m['title'],$position]);
            foreach($m['questions'] as $q) {
                $solution=$q['solution']??['correct'=>$q['correct']??null];
                $config=$q['config']??[];
                $qst->execute([$q['id'],$moduleId,$batch['id'],$q['type'],$q['prompt'],$q['difficulty']??2,$q['exam_part']??'AP1',$q['explanation']??'',json_encode($solution,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),json_encode($config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'active']);
                if(!$qst->rowCount())continue;
                foreach($q['options']??[] as $i=>$answer)$opt->execute([$q['id'],$i,(string)$answer,(int)($i===($q['correct']??-1))]);
                foreach($q['assets']??[] as $order=>$media) {
                    $path=(string)($media['path']??'');
                    if(!preg_match('~^assets/[a-zA-Z0-9/_-]+\\.(?:png|jpg|jpeg|webp|svg)$~',$path)||str_contains($path,'..'))throw new InvalidArgumentException('Ungültiger Bildpfad');
                    $placement=$media['placement']??'prompt';
                    if(!in_array($placement,['prompt','solution'],true))throw new InvalidArgumentException('Ungültige Bildposition');
                    $asset->execute([$q['id'],$path,'image',$media['alt']??'',$placement,$media['caption']??'',$order]);
                }
                $count++;
            }
        }
        $db->prepare('INSERT INTO learning_imports(id,count) VALUES(?,?)')->execute([$batch['id'],$count]);
        $db->commit();
        return $count;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

/** Explicit approved imports only; never re-import the retired legacy catalog. */
function import_pending_learning(PDO $db): void {
    import_learning_batch($db, json_decode((string)file_get_contents(dirname(__DIR__).'/content/ap1/passungen-h7-20261030.json'), true, 512, JSON_THROW_ON_ERROR));
    $spsBatch = json_decode((string)file_get_contents(dirname(__DIR__).'/content/ap1/sps-20261028.json'), true, 512, JSON_THROW_ON_ERROR);
    import_learning_batch($db, $spsBatch);
    // SPS question material is on hold pending reviewed teaching material.
    // Archive, never delete: stable IDs, attempts, revisions and XP remain intact.
    // Run after the idempotent import so fresh and existing databases behave alike.
    $archiveSps=$db->prepare("UPDATE learning_questions SET status='archived',updated_at=CURRENT_TIMESTAMP WHERE id LIKE 'ap1-sps-%' AND status IN ('active','draft','disabled')");
    $archiveSps->execute();
    $archiveSpsModules=$db->prepare("UPDATE learning_modules SET status='archived' WHERE id IN ('elektro:sps-grundlagen','elektro:sps-logik','elektro:sps-zeiten') AND status='active'");
    $archiveSpsModules->execute();
    import_learning_batch($db,[
        'id'=>'test-bohren-20261024',
        'title'=>'Erste Testaufgabe – Bohren',
        'origin'=>'Freigegebene technische Testaufgabe',
        'subject'=>'metall',
        'subject_title'=>'Metallbau',
        'modules'=>[[
            'id'=>'bohren-test',
            'title'=>'Bohren – Testaufgabe',
            'questions'=>[[
                'id'=>'ap1-metall-bohren-test-001',
                'type'=>'numeric',
                'prompt'=>'Ein Spiralbohrer mit 10 mm Durchmesser soll mit einer Schnittgeschwindigkeit von 25 m/min arbeiten. Berechne die erforderliche Drehzahl n in U/min. Runde auf eine ganze Zahl.',
                'difficulty'=>2,
                'solution'=>['value'=>796,'tolerance'=>1],
                'explanation'=>'n = (1000 × v_c) / (π × d) = (1000 × 25) / (π × 10) ≈ 795,8 U/min. Gerundet: 796 U/min.',
                'config'=>['unit'=>'U/min'],
                'assets'=>[
                    ['path'=>'assets/bohren/test-bohrer.svg','placement'=>'prompt','alt'=>'Spiralbohrer mit Durchmesser d und Drehbewegung','caption'=>''],
                    ['path'=>'assets/bohren/test-bohrer.svg','placement'=>'solution','alt'=>'Spiralbohrer als Bezug für die Drehzahlberechnung','caption'=>'Drehzahl: n = 1000 · v_c / (π · d) ≈ 796 U/min']
                ]
            ]]
        ]]
    ]);

    import_learning_batch($db,[
        'id'=>'bohren-schnittgeschwindigkeit-20261025',
        'title'=>'Bohren – Schnittgeschwindigkeit',
        'origin'=>'Freigabe im MechaTraining-Chat vom 25.10.2026',
        'subject'=>'metall',
        'subject_title'=>'Metallbau',
        'modules'=>[[
            'id'=>'bohren-test',
            'title'=>'Bohren – Testaufgabe',
            'questions'=>[[
                'id'=>'ap1-metall-bohren-002',
                'type'=>'numeric',
                'prompt'=>'An einer Ständerbohrmaschine wird mit einem Spiralbohrer von 12 mm Durchmesser gearbeitet. Die Maschinenspindel dreht sich mit 800 U/min. Berechne die Schnittgeschwindigkeit v_c in m/min. Runde auf eine Nachkommastelle.',
                'difficulty'=>2,
                'solution'=>['value'=>30.2,'tolerance'=>0.05],
                'explanation'=>'v_c = (π × d × n) / 1000 = (π × 12 × 800) / 1000 ≈ 30,159 m/min. Gerundet: 30,2 m/min.',
                'config'=>[
                    'unit'=>'m/min',
                    'hints'=>[
                        'symbols'=>'d = Bohrerdurchmesser (mm); n = Drehzahl (U/min); v_c = Schnittgeschwindigkeit (m/min).',
                        'text'=>'Überlege, welche Strecke die äußere Schneide bei einer Umdrehung zurücklegt und wie oft sie diese Strecke pro Minute zurücklegt.',
                        'book'=>'Tabellenbuch Metall, 50. Auflage: Schnittgeschwindigkeit, Seite 14.',
                        'formula'=>'v_c = (π × d × n) / 1000'
                    ]
                ]
            ]]
        ]]
    ]);

    import_learning_batch($db,[
        'id'=>'bohren-drehzahlwahl-20261025',
        'title'=>'Bohren – Drehzahlstufe wählen',
        'origin'=>'Freigegebene AP1-Praxisaufgabe',
        'subject'=>'metall',
        'subject_title'=>'Metallbau',
        'modules'=>[[
            'id'=>'bohren-test',
            'title'=>'Bohren – Testaufgabe',
            'questions'=>[[
                'id'=>'ap1-metall-bohren-003',
                'type'=>'multiple_choice',
                'prompt'=>'Du möchtest mit einem Spiralbohrer von 8 mm Durchmesser in Baustahl bohren. Die vorgegebene Schnittgeschwindigkeit beträgt 25 m/min. An der Ständerbohrmaschine stehen 630, 800, 1000, 1250, 1600 und 2000 U/min zur Verfügung. Welche Drehzahl wählst du, damit die vorgegebene Schnittgeschwindigkeit möglichst gut eingehalten wird?',
                'difficulty'=>2,
                'options'=>['630 U/min','800 U/min','1000 U/min','1250 U/min','1600 U/min','2000 U/min'],
                'correct'=>2,
                'explanation'=>'n = (1000 × 25) / (π × 8) ≈ 994,7 U/min. Die verfügbare Stufe 1000 U/min liegt dem Sollwert am nächsten.',
                'config'=>['hints'=>[
                    'symbols'=>'d = Bohrerdurchmesser (mm); v_c = Schnittgeschwindigkeit (m/min); n = Drehzahl (U/min).',
                    'text'=>'Berechne zunächst die theoretische Drehzahl und vergleiche sie dann mit den verfügbaren Drehzahlstufen.',
                    'book'=>'Tabellenbuch Metall, 50. Auflage: Schnittgeschwindigkeit, Seite 14.',
                    'formula'=>'n = (1000 × v_c) / (π × d)'
                ]]
            ]]
        ]]
    ]);

    import_learning_batch($db,[
      'id'=>'bohren-bauteile-20261025','title'=>'Bohren – Spiralbohrer beschriften',
      'origin'=>'Freigegebenes Berufsschul-Arbeitsblatt','subject'=>'metall','subject_title'=>'Metallbau',
      'modules'=>[['id'=>'bohren-test','title'=>'Bohren – Testaufgabe','questions'=>[[
        'id'=>'ap1-metall-bohren-004','type'=>'matching',
        'prompt'=>'Ordne die sechs nummerierten Stellen am Spiralbohrer den richtigen Fachbegriffen zu.',
        'difficulty'=>2,
        'solution'=>['answers'=>['Spitzenwinkel','Hauptschneide','Nebenschneide','Führungsfase','Spannut','Querschneide']],
        'explanation'=>'1 Spitzenwinkel: Winkel zwischen den Hauptschneiden. 2 Nebenschneide: Schneidkante am Bohrerumfang. 3 Spannut: schraubenförmige Nut für die Spanabfuhr. 4 Führungsfase: schmaler Streifen am Umfang zur Führung. 5 Hauptschneide: Schneide an der Bohrerspitze. 6 Querschneide: kurze Schneide im Zentrum.',
        'config'=>['labels'=>['Spitzenwinkel','Nebenschneide','Hauptschneide','Führungsfase','Spannut','Querschneide'],'hints'=>['text'=>'Unterscheide Seitenansicht und Stirnansicht. Die Schneiden an der Spitze und die Führungs- und Spannuten am Umfang erfüllen verschiedene Aufgaben.']],
        'assets'=>[['path'=>'assets/bohren/spiralbohrer-zuordnung-004.svg','placement'=>'prompt','alt'=>'Spiralbohrer mit sechs Nummern in Seiten- und Stirnansicht']]
      ]]]]
    ]);


    import_learning_batch($db,[
      'id'=>'gewindeschneiden-werkzeuge-20261025',
      'title'=>'Werkzeuge zum Gewindeschneiden',
      'origin'=>'Berufsschul-Arbeitsblatt',
      'subject'=>'metall','subject_title'=>'Metallbau',
      'modules'=>[[
        'id'=>'gewindeschneiden','title'=>'Gewindeschneiden',
        'questions'=>[[
          'id'=>'ap1-metall-gewindeschneiden-001',
          'type'=>'matching',
          'prompt'=>'Benenne Werkzeug 1 und Werkzeug 2 und ordne jeweils den richtigen Einsatzzweck zu.',
          'solution'=>['answers'=>[
            'Windeisen – Innengewinde schneiden',
            'Schneideisenhalter – Außengewinde schneiden'
          ]],
          'explanation'=>'Das Windeisen hält den Gewindebohrer zum Schneiden von Innengewinden. Der Schneideisenhalter hält das Schneideisen zum Schneiden von Außengewinden.',
          'config'=>['labels'=>[
            'Windeisen – Innengewinde schneiden',
            'Schneideisenhalter – Außengewinde schneiden'
          ]]
        ]]
      ]]
    ]);

    // Upgrade the existing tool question in place; preserve its ID and uploaded assets.
    $db->exec("CREATE TABLE IF NOT EXISTS learning_system_state (name TEXT PRIMARY KEY, value TEXT NOT NULL)");
    $key='thread_tools_mixed_v1';
    $check=$db->prepare('SELECT 1 FROM learning_system_state WHERE name=?');$check->execute([$key]);
    if(!$check->fetchColumn()){
      $db->beginTransaction();
      try{
        $solution=['tools'=>[
          ['names'=>['Windeisen','Gewindebohrerhalter','verstellbares Windeisen'],'correct'=>0],
          ['names'=>['Schneideisenhalter','Schneideisenhalterung'],'correct'=>1]
        ]];
        $config=['uses'=>[
          'Zum Halten und Drehen eines Gewindebohrers beim Schneiden von Innengewinden',
          'Zum Halten und Drehen eines Schneideisens beim Schneiden von Außengewinden',
          'Zum Festspannen eines Werkstücks beim Bohren',
          'Zum Nachbearbeiten einer Bohrung mit einer Reibahle'
        ]];
        $stmt=$db->prepare("UPDATE learning_questions SET type='tool_identification',prompt=?,solution_data=?,config_json=?,solution_explanation=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND type='matching'");
        $stmt->execute([
          'Benenne die Werkzeuge 1 und 2. Schreibe ihre Namen selbst auf und wähle jeweils den richtigen Einsatzzweck.',
          json_encode($solution,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
          json_encode($config,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
          '1: Windeisen – hält den Gewindebohrer und dient zum Schneiden von Innengewinden. 2: Schneideisenhalter – hält das Schneideisen und dient zum Schneiden von Außengewinden.',
          'ap1-metall-gewindeschneiden-001'
        ]);
        if($stmt->rowCount()===1){$done=$db->prepare('INSERT INTO learning_system_state(name,value) VALUES(?,?)');$done->execute([$key,'1']);}
        $db->commit();
      }catch(Throwable $e){$db->rollBack();throw $e;}
    }

    import_learning_batch($db,[
      'id'=>'senker-werkzeuge-20261025',
      'title'=>'Kegelsenker und Zapfensenker erkennen',
      'origin'=>'Freigabe der Senker-Zeichnung im MechaTraining-Chat',
      'subject'=>'metall','subject_title'=>'Metallbau',
      'modules'=>[[
        'id'=>'senken','title'=>'Senken',
        'questions'=>[[
          'id'=>'ap1-metall-senken-001',
          'type'=>'tool_identification',
          'prompt'=>'Benenne die Werkzeuge 1 und 2. Gib die Werkzeugnamen selbst ein und wähle jeweils den richtigen Einsatzzweck.',
          'difficulty'=>2,
          'solution'=>['tools'=>[
            ['names'=>['90° Kegelkopfsenker','90°-Kegelkopfsenker','Kegelkopfsenker','90° Kegelsenker','90°-Kegelsenker','Kegelsenker'],'correct'=>0],
            ['names'=>['Zapfensenker','Flachsenker mit Führungszapfen','Zylindersenker mit Führungszapfen'],'correct'=>1]
          ]],
          'explanation'=>'1: Der 90°-Kegelsenker (auch Kegelkopfsenker) erzeugt kegelförmige Senkungen für Senkkopfschrauben mit 90°-Kopf. 2: Der Zapfensenker erzeugt zylindrische, flachbodige Senkungen, beispielsweise für Zylinderkopfschrauben. Sein Führungszapfen zentriert das Werkzeug in der vorhandenen Bohrung.',
          'config'=>['uses'=>[
            'Eine kegelförmige 90°-Senkung für Senkkopfschrauben herstellen',
            'Eine zylindrische, flachbodige Senkung mit Führungszapfen herstellen',
            'Ein Innengewinde in eine Kernlochbohrung schneiden',
            'Eine Bohrung mit einer Reibahle maßgenau fertigbearbeiten'
          ]]
        ]]
      ]]
    ]);

    import_learning_batch($db,[
      'id'=>'feile-bauteile-20261025',
      'title'=>'Feile – Bauteile zuordnen',
      'origin'=>'Berufsschul-Arbeitsblatt und nummerierte Feilengrafik vom 25.10.2026',
      'subject'=>'metall','subject_title'=>'Metallbau',
      'modules'=>[[
        'id'=>'feilen','title'=>'Feilen',
        'questions'=>[[
          'id'=>'ap1-metall-feilen-001',
          'type'=>'matching',
          'prompt'=>'Ordne den Nummern 1 bis 5 an der Feile die richtigen Fachbegriffe zu.',
          'difficulty'=>1,
          'solution'=>['answers'=>['Griff','Angel','Hiebnummer','Blatt','Kopf']],
          'explanation'=>'1 Griff: sicherer Halt. 2 Angel: verbindet das Feilenblatt mit dem Griff. 3 Hiebnummer: kennzeichnet die Feinheit des Feilenhiebs. 4 Blatt: Arbeitsbereich der Feile mit den Schneidzähnen. 5 Kopf: vorderes Ende des Feilenblatts.',
          'config'=>['labels'=>['Griff','Angel','Hiebnummer','Blatt','Kopf'],'hints'=>['text'=>'Achte auf den Griff, den schmalen Übergang, die eingeprägte Zahl und die beiden Bereiche des Feilenblatts.']]
        ]]
      ]]
    ]);

    // One-time correction of the previously imported matching answer key.
    $fixKey = 'bohren_004_key_corrected';
    $db->exec("CREATE TABLE IF NOT EXISTS learning_system_state (name TEXT PRIMARY KEY, value TEXT NOT NULL)");
    $state = $db->prepare('SELECT value FROM learning_system_state WHERE name=?');
    $state->execute([$fixKey]);
    if ($state->fetchColumn() === false) {
        $lookup = $db->prepare('SELECT solution_data FROM learning_questions WHERE id=?');
        $lookup->execute(['ap1-metall-bohren-004']);
        $original = $lookup->fetchColumn();
        if ($original !== false) {
            $solution = json_decode((string)$original, true);
            if (($solution['answers'] ?? []) === ['Spitzenwinkel','Nebenschneide','Spannut','Führungsfase','Hauptschneide','Querschneide']) {
                $solution['answers'] = ['Spitzenwinkel','Hauptschneide','Nebenschneide','Führungsfase','Spannut','Querschneide'];
                $explanation = '1 Spitzenwinkel. 2 Hauptschneide. 3 Nebenschneide. 4 Führungsfase. 5 Spannut. 6 Querschneide.';
                $update = $db->prepare('UPDATE learning_questions SET solution_data=?, solution_explanation=? WHERE id=?');
                $update->execute([json_encode($solution, JSON_UNESCAPED_UNICODE), $explanation, 'ap1-metall-bohren-004']);
            }
        }
        $db->prepare('INSERT INTO learning_system_state(name,value) VALUES(?,CURRENT_TIMESTAMP)')->execute([$fixKey]);
    }

}

function catalog_modules(PDO $db): array {
    $rows=$db->query("SELECT q.id,q.module_id,q.type,q.solution_data,q.prompt,q.solution_explanation,q.config_json,m.title AS module_title,m.subject_id,s.title AS subject_title,m.sort_order FROM learning_questions q JOIN learning_modules m ON m.id=q.module_id JOIN subjects s ON s.id=m.subject_id WHERE q.status='active' AND m.status='active' AND s.status='active' ORDER BY s.sort_order,m.sort_order,q.id")->fetchAll();
    $options=$db->query('SELECT question_id,option_index,text,is_correct FROM question_options ORDER BY question_id,option_index')->fetchAll();
    $media=$db->query('SELECT question_id,asset_path,alt_text,placement,caption FROM question_assets ORDER BY sort_order,id')->fetchAll();
    $mediaByQuestion=[];foreach($media as $item)$mediaByQuestion[$item['question_id']][]=['path'=>$item['asset_path'],'alt'=>$item['alt_text'],'placement'=>$item['placement'],'caption'=>$item['caption']];
    $byQuestion=[];foreach($options as $o)$byQuestion[$o['question_id']][]=$o;
    $out=[];foreach($rows as $r) {
        $qopts=$byQuestion[$r['id']]??[];$correct=0;$texts=[];
        foreach($qopts as $o){$texts[]=$o['text'];if((int)$o['is_correct']===1)$correct=(int)$o['option_index'];}
        $config=json_decode($r['config_json'],true)?:[];
        $q=['id'=>$r['id'],'type'=>$r['type'],'prompt'=>$r['prompt'],'options'=>$texts,'correct'=>$correct,'explanation'=>$r['solution_explanation']];
        $q['config']=$config;
        $q['assets']=$mediaByQuestion[$r['id']]??[];
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
    $db->exec("CREATE TABLE IF NOT EXISTS learning_imports (id TEXT PRIMARY KEY, imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, count INTEGER NOT NULL)");
    $done=$db->query("SELECT value FROM learning_system_state WHERE name='clean_reset_20261024'")->fetchColumn();
    if($done!==false)return;
    $db->beginTransaction();
    try {
        foreach(['question_attempts','question_tags','question_revisions','question_assets','question_options','learning_questions','learning_modules','subjects','learning_sources','progress','futter_status','futter_events','futter_deleted_batches','learning_imports'] as $table) {
            $db->exec("DELETE FROM ".$table);
        }
        $db->exec("INSERT INTO learning_system_state(name,value) VALUES('clean_reset_20261024',CURRENT_TIMESTAMP)");
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
