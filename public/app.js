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
function openExercise(module,back){questions=module.questions;returnView=back;document.querySelector('#appPanel h2').textContent=module.title;document.querySelector('#appPanel .toolbar .eyebrow').textContent=back==='pneuPanel'?'PNEUMATIK · AP1':back==='metalPanel'?'METALLBAU · AP1':'ELEKTROTECHNIK · AP1';document.querySelector('#appPanel .toolbar p + h2');document.querySelector('#appPanel .toolbar + p').replaceChildren(document.createTextNode('Angemeldet als '));const line=document.querySelector('#appPanel .toolbar + p');const user=document.createElement('strong');user.id='username';user.textContent=loggedName;const count=document.createElement('span');count.id='progress';line.append(user,document.createTextNode(' · '),count,document.createTextNode(' von '+questions.length+' Aufgaben richtig gelöst'));$('exerciseBack').textContent=back==='pneuPanel'?'← Pneumatik':back==='metalPanel'?'← Metallbau':'← Elektrotechnik';render();showLearningView('appPanel');rememberView('appPanel',module.id||'electro');}
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

function render(){
 const root=$('quiz');root.replaceChildren();
 questions.forEach((q,i)=>{
  const section=document.createElement('section');section.className='question';
  const title=document.createElement('h3');title.textContent=(i+1)+'. '+q.prompt;section.append(title);
  const options=document.createElement('div');options.className='options';
  const feedback=document.createElement('p');feedback.className='result';feedback.setAttribute('aria-live','polite');
  q.options.forEach((answer,index)=>{
   const button=document.createElement('button');button.textContent=answer;button.disabled=solved.has(q.id);
   button.addEventListener('click',async()=>{
    options.querySelectorAll('button').forEach(b=>b.disabled=true);
    try{const r=await api('answer',{question_id:q.id,answer:index});
     feedback.textContent=(r.correct?'✓ Richtig! ':'✕ Noch nicht richtig. ')+r.explanation;
     if(r.correct){solved.add(q.id);progress();await refreshXP();}else{options.querySelectorAll('button').forEach(b=>b.disabled=false);}
    }catch(e){feedback.textContent=e.message;options.querySelectorAll('button').forEach(b=>b.disabled=false);}
   });options.append(button);
  });if(q.diagram)pneumaticDiagram(section);section.append(options,feedback);root.append(section);
 });
 progress();
}
async function signedIn(user,token){csrf=token;loggedName=user.name;$('username').textContent=user.name;$('loginPanel').classList.add('hidden');$('dashboard').classList.remove('hidden');const data=await api('progress');solved=new Set(data.progress.filter(p=>Number(p.correct)===1).map(p=>p.question_id));render();updateXP(Number(data.xp)||0);$('xpWidget').classList.remove('hidden');if(user.role==='admin'){$('adminToggle').classList.remove('hidden');await loadUsers();}const previous=savedView();if(previous&&previous.user===user.name){if(previous.view==='formulaPanel'){$('formulaOpen').click();}else if(previous.view==='appPanel'){const module=[...pneumaticModules,...metalModules].find(m=>m.id===previous.moduleId);if(module)openExercise(module,metalModules.includes(module)?'metalPanel':'pneuPanel');else if(previous.moduleId==='electro')openExercise({id:'electro',title:'Elektrotechnik – Grundlagen',questions:electroQuestions},'topicPanel');}else if(['dashboard','topicPanel','metalPanel','pneuPanel'].includes(previous.view))showLearningView(previous.view);}}
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

document.querySelector('[data-topic="metall"]').addEventListener('click',()=>showLearningView('metalPanel'));
$('metalBack').addEventListener('click',()=>showLearningView('dashboard'));

document.querySelector('[data-topic="pneumatik"]').addEventListener('click',()=>showLearningView('pneuPanel'));
$('pneuBack').addEventListener('click',()=>showLearningView('dashboard'));
for(const module of pneumaticModules){const button=document.createElement('button');button.className='exercise-card';button.type='button';const name=document.createElement('strong');name.textContent=module.title;const detail=document.createElement('small');detail.textContent=module.questions.length+' Aufgaben · Gespeicherter Fortschritt';const arrow=document.createElement('span');arrow.textContent='Öffnen →';button.append(name,detail,arrow);button.addEventListener('click',()=>openExercise(module,'pneuPanel'));$('pneuModules').append(button);}

// Metallbau: vier Schweißtechnik-Module mit dauerhaft eindeutigen Aufgaben-IDs
for(const module of metalModules){const button=document.createElement('button');button.className='exercise-card';button.type='button';const name=document.createElement('strong');name.textContent=module.title;const detail=document.createElement('small');detail.textContent=module.questions.length+' Aufgaben · Gespeicherter Fortschritt';const arrow=document.createElement('span');arrow.textContent='Öffnen →';button.append(name,detail,arrow);button.addEventListener('click',()=>openExercise(module,'metalPanel'));$('metalModules').append(button);}
