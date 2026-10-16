const questions=JSON.parse(document.getElementById('questions').textContent);
let csrf='',solved=new Set();
const $=id=>document.getElementById(id);
async function api(action,data){
 const response=await fetch('api.php?action='+encodeURIComponent(action),{method:data?'POST':'GET',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:data?JSON.stringify(data):undefined});
 const result=await response.json();if(!response.ok)throw Error(result.error||'Serverfehler');return result;
}
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
     if(r.correct){solved.add(q.id);progress();}else{options.querySelectorAll('button').forEach(b=>b.disabled=false);}
    }catch(e){feedback.textContent=e.message;options.querySelectorAll('button').forEach(b=>b.disabled=false);}
   });options.append(button);
  });section.append(options,feedback);root.append(section);
 });
 progress();
}
async function signedIn(user,token){csrf=token;$('username').textContent=user.name;$('loginPanel').classList.add('hidden');$('appPanel').classList.remove('hidden');const data=await api('progress');solved=new Set(data.progress.filter(p=>Number(p.correct)===1).map(p=>p.question_id));render();}
$('loginForm').addEventListener('submit',async e=>{e.preventDefault();$('loginError').textContent='';const form=new FormData(e.currentTarget);try{const r=await api('login',{username:form.get('username'),password:form.get('password')});await signedIn(r.user,r.csrf);}catch(err){$('loginError').textContent=err.message;}});
$('logout').addEventListener('click',async()=>{try{await api('logout',{});location.reload();}catch(e){alert(e.message);}});
api('session').then(r=>{if(r.user)return signedIn(r.user,r.csrf);}).catch(e=>{$('loginError').textContent=e.message;});
