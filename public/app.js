const electroQuestions=JSON.parse(document.getElementById('questions').textContent);
const pneumaticModules=JSON.parse(document.getElementById('pneumatics').textContent).modules;
const metalModules=JSON.parse(document.getElementById('metalworks').textContent).modules;
let questions=electroQuestions, returnView='topicPanel';
let csrf='',solved=new Set();
const viewKey='mechatraining-view';
function rememberView(view,moduleId){try{sessionStorage.setItem(viewKey,JSON.stringify({view,moduleId:moduleId||null,user:loggedName}));}catch(e){}}
function savedView(){try{return JSON.parse(sessionStorage.getItem(viewKey)||'null');}catch(e){return null;}}
function clearView(){try{sessionStorage.removeItem(viewKey);}catch(e){}}


const $=id=>document.getElementById(id);
async function api(action,data){
 const response=await fetch('api.php?action='+encodeURIComponent(action),{method:data?'POST':'GET',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:data?JSON.stringify(data):undefined});
 const result=await response.json();if(!response.ok)throw Error(result.error||'Serverfehler');return result;
}
function updateXP(xp){const level=Math.floor(xp/100)+1;const current=xp%100;$('xpLevel').textContent='Level '+level;$('xpText').textContent=current+' / 100 XP';$('xpFill').style.width=current+'%';$('xpTrack').setAttribute('aria-valuenow',String(current));}
async function refreshXP(){const data=await api('progress');updateXP(Number(data.xp)||0);}
function progress(){const count=questions.filter(q=>solved.has(q.id)).length;$('progress').textContent=count;$('bar').style.width=(100*count/questions.length)+'%'; }
let activeAreaIds=new Set(),availableAreas=[];
function quizAreas(back){if(back==='dashboard')return [{id:'electro',title:'Elektrotechnik – Grundlagen',questions:electroQuestions},...metalModules,...pneumaticModules];return back==='pneuPanel'?pneumaticModules:back==='metalPanel'?metalModules:[{id:'electro',title:'Elektrotechnik – Grundlagen',questions:electroQuestions}];}
function applyAreaFilter(){
 const selected=availableAreas.filter(m=>activeAreaIds.has(m.id));
 questions=selected.flatMap(m=>m.questions);
 quizIndex=0;
 const options=$('quizAreaOptions');
 options.querySelectorAll('input').forEach(input=>{input.disabled=activeAreaIds.size===1&&input.checked;});
 $('quizAreaHint').textContent=questions.length+' Aufgaben ausgewählt';
 const line=document.querySelector('#appPanel .toolbar + p');
 const progressNode=$('progress');
 if(progressNode){let next=progressNode.nextSibling;if(next)next.textContent=' von '+questions.length+' Aufgaben richtig gelöst';}
 render();
}
function openExercise(module,back){
 returnView=back;availableAreas=quizAreas(back);activeAreaIds=module.id==='all'?new Set(availableAreas.map(m=>m.id)):new Set([module.id]);
 document.querySelector('#appPanel h2').textContent=module.title;
 document.querySelector('#appPanel .toolbar .eyebrow').textContent=back==='dashboard'?'MECHA-QUIZ · AP1':back==='pneuPanel'?'PNEUMATIK · AP1':back==='metalPanel'?'METALLBAU · AP1':'ELEKTROTECHNIK · AP1';
 const line=document.querySelector('#appPanel .toolbar + p');line.replaceChildren(document.createTextNode('Angemeldet als '));
 const user=document.createElement('strong');user.id='username';user.textContent=loggedName;
 const count=document.createElement('span');count.id='progress';
 line.append(user,document.createTextNode(' · '),count,document.createTextNode(' von 0 Aufgaben richtig gelöst'));
 $('exerciseBack').textContent=back==='dashboard'?'← Startseite':back==='pneuPanel'?'← Pneumatik':back==='metalPanel'?'← Metallbau':'← Elektrotechnik';
 const root=$('quizAreaOptions');root.replaceChildren();
 availableAreas.forEach(area=>{
  const label=document.createElement('label');label.className='quiz-area-choice';
  const input=document.createElement('input');input.type='checkbox';input.value=area.id;input.checked=activeAreaIds.has(area.id);
  const caption=document.createElement('span');caption.textContent=area.title+' ('+area.questions.length+')';
  input.addEventListener('change',()=>{if(input.checked)activeAreaIds.add(area.id);else activeAreaIds.delete(area.id);applyAreaFilter();});
  label.append(input,caption);root.append(label);
 });
 applyAreaFilter();showLearningView('appPanel');rememberView('appPanel',module.id||'electro');
}
let loggedName='';
function pneumaticDiagram(section){
 const wrap=document.createElement('div');wrap.className='pneu-simulator';
 const title=document.createElement('p');title.textContent='Schaltung erkunden: Steuersignal 14 / 12 betätigen';wrap.append(title);
 const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 500 190');svg.setAttribute('role','img');svg.setAttribute('aria-label','Vereinfachter doppeltwirkender Zylinder mit 5/2-Wegeventil und Leitungen');
 svg.innerHTML='<rect x="165" y="15" width="170" height="52" rx="3" fill="none" stroke="currentColor" stroke-width="3"/><line x1="200" y1="15" x2="200" y2="67" stroke="currentColor" stroke-width="5"/><line x1="200" y1="40" x2="392" y2="40" stroke="currentColor" stroke-width="8"/><path d="M185 67 V103 H210 V125 M315 67 V103 H290 V125" fill="none" stroke="#83bdd8" stroke-width="3"/><rect x="200" y="125" width="100" height="42" fill="none" stroke="currentColor" stroke-width="3"/><line x1="250" y1="125" x2="250" y2="167" stroke="currentColor"/><path d="M250 167 V185 M210 167 V185 M290 167 V185" stroke="currentColor" stroke-width="3"/><text x="173" y="10" fill="currentColor" font-size="13">1A</text><text x="210" y="120" fill="currentColor" font-size="12">4</text><text x="280" y="120" fill="currentColor" font-size="12">2</text><text x="255" y="180" fill="currentColor" font-size="12">1</text>';
 const piston=svg.querySelector('line[stroke-width="5"]');const rod=svg.querySelector('line[stroke-width="8"]');
 const state=document.createElement('p');state.className='pneu-state';state.setAttribute('aria-live','polite');
 const controls=document.createElement('div');controls.className='pneu-controls';
 function set(side){const extend=side==='14';piston.setAttribute('x1',extend?'310':'200');piston.setAttribute('x2',extend?'310':'200');rod.setAttribute('x1',extend?'310':'200');state.textContent=extend?'Signal 14 → 1–4 / 2–3 · Zylinder fährt aus':'Signal 12 → 1–2 / 4–5 · Zylinder fährt ein';}
 for(const [signal,label] of [['14','Signal 14 · Ausfahren'],['12','Signal 12 · Einfahren']]){const b=document.createElement('button');b.type='button';b.textContent=label;b.addEventListener('click',()=>set(signal));controls.append(b);}
 wrap.append(svg,controls,state);set('12');section.append(wrap);
}

let quizIndex=0,quizTimer=null;
function render(){
 clearTimeout(quizTimer);
 const root=$('quiz');root.replaceChildren();
 if(!questions.length){root.textContent='Keine Aufgaben ausgewählt.';progress();return;}
 quizIndex=Math.max(0,Math.min(quizIndex,questions.length-1));
 const q=questions[quizIndex];
 const section=document.createElement('section');section.className='question quiz-single-card';
 const counter=document.createElement('p');counter.className='quiz-step';counter.textContent='AUFGABE '+(quizIndex+1)+' / '+questions.length;
 const title=document.createElement('h3');title.textContent=q.prompt;section.append(counter,title);
 if(window.mechaAdmin===true){const edit=document.createElement('button');edit.type='button';edit.className='quiz-question-edit';edit.dataset.editQuestion=q.id;edit.textContent='✎';edit.title='Aufgabe bearbeiten';edit.setAttribute('aria-label','Aufgabe bearbeiten');section.append(edit);}
 window.MechaQuestionImages?.append(section,q,'prompt');
 const hints=q.config?.hints||{};const hintValues=[['symbols','Formelzeichen'],['text','Texttipp'],['book','Buchtipp']];
 if(hintValues.some(([key])=>String(hints[key]||'').trim())){
  const jokers=document.createElement('div');jokers.className='quiz-jokers';
  for(const [key,label] of hintValues){if(!String(hints[key]||'').trim())continue;const details=document.createElement('details');details.className='quiz-joker quiz-joker--'+key;const summary=document.createElement('summary');summary.textContent='Joker: '+label;const content=document.createElement('p');content.textContent=hints[key];details.append(summary,content);jokers.append(details);}
  section.append(jokers);
 }
 const options=document.createElement('div');options.className='options';
 const feedback=document.createElement('div');feedback.className='result quiz-answer-feedback';feedback.setAttribute('role','status');
 const actions=document.createElement('div');actions.className='quiz-step-actions';
 const next=document.createElement('button');next.type='button';next.className='quiz-next-button';next.textContent=quizIndex===questions.length-1?'Von vorn ↻':'Nächste Aufgabe →';next.hidden=true;
 next.addEventListener('click',()=>{quizIndex=(quizIndex+1)%questions.length;render();});
 actions.append(next);
 let finished=false;
 if(q.type==='numeric'||q.type==='text_input'){
  const form=document.createElement('form');form.className='quiz-input-form';
  const input=document.createElement('input');input.type='text';input.required=true;input.autocomplete='off';input.placeholder=q.type==='numeric'?'Zahlenwert eingeben':'Antwort eingeben';input.setAttribute('aria-label','Deine Antwort');
  const submit=document.createElement('button');submit.type='submit';submit.textContent='Prüfen';
  form.append(input,submit);options.append(form);
  form.addEventListener('submit',async event=>{
   event.preventDefault();if(finished)return;submit.disabled=true;
   try{
    const result=await api('answer',{question_id:q.id,answer:input.value});
    finished=true;input.disabled=true;
    const heading=document.createElement('strong');heading.textContent=result.correct?'✓ Richtig!':'✕ Noch nicht richtig.';
    const explanation=document.createElement('p');explanation.textContent=result.explanation||q.explanation||'Bitte Lösung überprüfen.';
    feedback.replaceChildren(heading,explanation);window.MechaQuestionImages?.append(feedback,q,'solution');
    if(result.correct){document.dispatchEvent(new Event('mechatraining:correct'));if(!solved.has(q.id)){solved.add(q.id);progress();await refreshXP();}next.hidden=false;}
    else{document.dispatchEvent(new CustomEvent('mechatraining:wrong',{detail:{explanation:result.explanation||q.explanation||'Antwort nicht richtig.',onConfirm:()=>{next.hidden=false;}}}));next.hidden=false;}
   }catch(error){feedback.textContent=error.message;submit.disabled=false;}
  });
 }
 else q.options.forEach((answer,index)=>{
  const button=document.createElement('button');button.type='button';button.textContent=answer;
  button.addEventListener('click',async()=>{
   if(finished)return;
   options.querySelectorAll('button').forEach(b=>b.disabled=true);
   try{
    const alreadySolved=solved.has(q.id);
    const result=alreadySolved?{correct:index===q.correct,explanation:q.explanation}:await api('answer',{question_id:q.id,answer:index});
    finished=true;
    const heading=document.createElement('strong');heading.textContent=result.correct?'✓ Richtig!':'✕ Noch nicht richtig.';
    const explanation=document.createElement('p');explanation.textContent=result.explanation||q.explanation||'Die richtige Antwort ist: '+q.options[q.correct];
    feedback.classList.toggle('quiz-feedback-wrong',!result.correct);
    feedback.replaceChildren(heading,explanation);window.MechaQuestionImages?.append(feedback,q,'solution');
    if(result.correct){
     button.classList.add('quiz-answer-correct');
     document.dispatchEvent(new Event('mechatraining:correct'));
     if(!alreadySolved){solved.add(q.id);progress();await refreshXP();}
     quizTimer=setTimeout(()=>{quizIndex=(quizIndex+1)%questions.length;render();},1450);
    }else{
     document.dispatchEvent(new CustomEvent('mechatraining:wrong',{detail:{explanation:'Richtige Antwort: '+q.options[q.correct]+'. '+(result.explanation||q.explanation||''),onConfirm:()=>{next.hidden=false;}}}));
     button.classList.add('quiz-answer-wrong');
     options.querySelectorAll('button')[q.correct]?.classList.add('quiz-answer-correct');
     next.hidden=false;
    }
   }catch(e){feedback.textContent=e.message;options.querySelectorAll('button').forEach(b=>b.disabled=false);}
  });options.append(button);
 });
 if(q.diagram)pneumaticDiagram(section);
 section.append(options,feedback,actions);root.append(section);progress();
}
async function signedIn(user,token){csrf=token;loggedName=user.name;$('username').textContent=user.name;$('loginPanel').classList.add('hidden');$('dashboard').classList.remove('hidden');const data=await api('progress');solved=new Set(data.progress.filter(p=>Number(p.correct)===1).map(p=>p.question_id));render();updateXP(Number(data.xp)||0);$('xpWidget').classList.remove('hidden');$('logout').classList.remove('hidden');$('passwordToggle').classList.remove('hidden');window.mechaAdmin=user.role==='admin';if(user.role==='admin'){$('adminToggle').classList.remove('hidden');await loadUsers();}const previous=savedView();if(previous&&previous.user===user.name){if(previous.view==='formulaPanel'){$('formulaOpen').click();}else if(previous.view==='appPanel'){const module=[...pneumaticModules,...metalModules].find(m=>m.id===previous.moduleId);if(module)openExercise(module,metalModules.includes(module)?'metalPanel':'pneuPanel');else if(previous.moduleId==='electro')openExercise({id:'electro',title:'Elektrotechnik – Grundlagen',questions:electroQuestions},'topicPanel');}else if(['dashboard','topicPanel','metalPanel','pneuPanel'].includes(previous.view))showLearningView(previous.view);}}
$('loginForm').addEventListener('submit',async e=>{e.preventDefault();$('loginError').textContent='';const form=new FormData(e.currentTarget);try{const r=await api('login',{username:form.get('username'),password:form.get('password')});await signedIn(r.user,r.csrf);}catch(err){$('loginError').textContent=err.message;}});
$('logout').addEventListener('click',async()=>{try{await api('logout',{});clearView();location.reload();}catch(e){alert(e.message);}});
api('session').then(r=>{if(r.user)return signedIn(r.user,r.csrf);$('loginPanel').classList.remove('hidden');}).catch(e=>{$('loginPanel').classList.remove('hidden');$('loginError').textContent=e.message;});

async function loadUsers(){const data=await api('users');const list=$('usersList');list.replaceChildren();data.users.forEach(user=>{const li=document.createElement('li');li.textContent=user.username+' ('+(user.role==='admin'?'Administrator':'Lernender')+')';list.append(li);});}
$('createUserForm').addEventListener('submit',async e=>{e.preventDefault();const form=new FormData(e.currentTarget);$('adminMessage').textContent='';try{await api('create_user',{username:form.get('username'),password:form.get('password')});$('adminMessage').textContent='Benutzer erfolgreich angelegt.';e.currentTarget.reset();await loadUsers();}catch(err){$('adminMessage').textContent=err.message;}});

// Navigation: Lernbereiche → Themenübungen → Aufgabe
function showLearningView(view){['dashboard','topicPanel','metalPanel','pneuPanel','appPanel'].forEach(id=>$(id).classList.toggle('hidden',id!==view));if(view!=='appPanel')rememberView(view);}
document.querySelector('[data-topic="elektro"]').addEventListener('click',()=>showLearningView('topicPanel'));
$('topicBack').addEventListener('click',()=>showLearningView('dashboard'));
$('openBasics').addEventListener('click',()=>openExercise({id:'electro',title:'Elektrotechnik – Grundlagen',questions:electroQuestions},'topicPanel'));
$('exerciseBack').addEventListener('click',()=>showLearningView(returnView));

document.querySelector('[data-topic="metall"]')?.addEventListener('click',()=>showLearningView('metalPanel'));
$('metalBack').addEventListener('click',()=>showLearningView('dashboard'));

document.querySelector('[data-topic="pneumatik"]').addEventListener('click',()=>showLearningView('pneuPanel'));
$('pneuBack').addEventListener('click',()=>showLearningView('dashboard'));
for(const module of pneumaticModules){const button=document.createElement('button');button.className='exercise-card';button.type='button';const name=document.createElement('strong');name.textContent=module.title;const detail=document.createElement('small');detail.textContent=module.questions.length+' Aufgaben · Gespeicherter Fortschritt';const arrow=document.createElement('span');arrow.textContent='Öffnen →';button.append(name,detail,arrow);button.addEventListener('click',()=>openExercise(module,'pneuPanel'));$('pneuModules').append(button);}

// Metallbau: vier Schweißtechnik-Module mit dauerhaft eindeutigen Aufgaben-IDs
for(const module of metalModules){const button=document.createElement('button');button.className='exercise-card';button.type='button';const name=document.createElement('strong');name.textContent=module.title;const detail=document.createElement('small');detail.textContent=module.questions.length+' Aufgaben · Gespeicherter Fortschritt';const arrow=document.createElement('span');arrow.textContent='Öffnen →';button.append(name,detail,arrow);button.addEventListener('click',()=>openExercise(module,'metalPanel'));$('metalModules').append(button);}

// Landing page shortcut to existing formula mode.
document.getElementById('formulaTile')?.addEventListener('click',()=>document.getElementById('formulaOpen')?.click());

// Quiz entry: all subjects are available through the shared area filter.
document.querySelector('[data-topic="elektro"]')?.addEventListener('click',e=>{e.stopImmediatePropagation();openExercise({id:'all',title:'Mecha-Quiz',questions:[...electroQuestions,...metalModules.flatMap(m=>m.questions),...pneumaticModules.flatMap(m=>m.questions)]},'dashboard');},true);

// Passwortverwaltung für angemeldete Benutzer
let passwordReturnView='dashboard';
$('passwordToggle').addEventListener('click',()=>{
 passwordReturnView=['dashboard','topicPanel','metalPanel','pneuPanel','appPanel','formulaPanel','adminHome','adminPanel','futterPanel'].find(id=>!$(id).classList.contains('hidden'))||'dashboard';
 document.querySelectorAll('main > section').forEach(el=>{if(el.id!=='loginPanel')el.classList.add('hidden');});
 $('passwordPanel').classList.remove('hidden');$('passwordMessage').textContent='';
});
$('passwordBack').addEventListener('click',()=>{
 $('passwordPanel').classList.add('hidden');$(passwordReturnView).classList.remove('hidden');
});
$('passwordForm').addEventListener('submit',async e=>{
 e.preventDefault();const form=e.currentTarget;const pass=form.elements.password.value;
 const message=$('passwordMessage');message.textContent='';
 if(pass!==form.elements.confirm.value){message.textContent='Die Passwörter stimmen nicht überein.';return;}
 try{const result=await api('change_password',{password:pass});csrf=result.csrf;form.reset();message.textContent='Passwort erfolgreich geändert.';}
 catch(err){message.textContent=err.message;}
});
