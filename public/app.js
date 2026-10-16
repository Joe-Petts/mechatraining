const questions=JSON.parse(document.getElementById('questions').textContent);
let csrf='',solved=new Set();

const $=id=>document.getElementById(id);
async function api(action,data){
 const response=await fetch('api.php?action='+encodeURIComponent(action),{method:data?'POST':'GET',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:data?JSON.stringify(data):undefined});
 const result=await response.json();if(!response.ok)throw Error(result.error||'Serverfehler');return result;
}
function updateXP(xp){const level=Math.floor(xp/100)+1;const current=xp%100;$('xpLevel').textContent='Level '+level;$('xpText').textContent=current+' / 100 XP';$('xpFill').style.width=current+'%';$('xpTrack').setAttribute('aria-valuenow',String(current));}
async function refreshXP(){const data=await api('progress');updateXP(Number(data.xp)||0);}
function progress(){ $('progress').textContent=solved.size;$('bar').style.width=(100*solved.size/questions.length)+'%'; }
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
  });section.append(options,feedback);root.append(section);
 });
 progress();
}
async function signedIn(user,token){csrf=token;$('username').textContent=user.name;$('loginPanel').classList.add('hidden');$('dashboard').classList.remove('hidden');const data=await api('progress');solved=new Set(data.progress.filter(p=>Number(p.correct)===1).map(p=>p.question_id));render();updateXP(Number(data.xp)||0);$('xpWidget').classList.remove('hidden');if(user.role==='admin'){$('adminPanel').classList.remove('hidden');await loadUsers();}}
$('loginForm').addEventListener('submit',async e=>{e.preventDefault();$('loginError').textContent='';const form=new FormData(e.currentTarget);try{const r=await api('login',{username:form.get('username'),password:form.get('password')});await signedIn(r.user,r.csrf);}catch(err){$('loginError').textContent=err.message;}});
$('logout').addEventListener('click',async()=>{try{await api('logout',{});location.reload();}catch(e){alert(e.message);}});
api('session').then(r=>{if(r.user)return signedIn(r.user,r.csrf);}).catch(e=>{$('loginError').textContent=e.message;});

async function loadUsers(){const data=await api('users');const list=$('usersList');list.replaceChildren();data.users.forEach(user=>{const li=document.createElement('li');li.textContent=user.username+' ('+(user.role==='admin'?'Administrator':'Lernender')+')';list.append(li);});}
$('createUserForm').addEventListener('submit',async e=>{e.preventDefault();const form=new FormData(e.currentTarget);$('adminMessage').textContent='';try{await api('create_user',{username:form.get('username'),password:form.get('password')});$('adminMessage').textContent='Benutzer erfolgreich angelegt.';e.currentTarget.reset();await loadUsers();}catch(err){$('adminMessage').textContent=err.message;}});

// Navigation: Lernbereiche → Themenübungen → Aufgabe
function showLearningView(view){['dashboard','topicPanel','appPanel'].forEach(id=>$(id).classList.toggle('hidden',id!==view));}
document.querySelector('[data-topic="elektro"]').addEventListener('click',()=>showLearningView('topicPanel'));
$('topicBack').addEventListener('click',()=>showLearningView('dashboard'));
$('openBasics').addEventListener('click',()=>showLearningView('appPanel'));
$('exerciseBack').addEventListener('click',()=>showLearningView('topicPanel'));
