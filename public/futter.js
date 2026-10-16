document.addEventListener('DOMContentLoaded',()=>{
 const $=id=>document.getElementById(id);let items=[];
 async function request(action,body){
  const token=await fetch('api.php?action=session',{credentials:'same-origin'}).then(r=>r.json());
  const r=await fetch('api.php?action='+action,{method:body?'POST':'GET',headers:{'Content-Type':'application/json','X-CSRF-Token':token.csrf||''},body:body?JSON.stringify(body):undefined,credentials:'same-origin'});
  const data=await r.json();if(!r.ok)throw Error(data.error||'Fehler');return data;
 }
 async function refresh(){
  const data=await request('futter_list');items=data.items;
  const events=$('futterEvents');events.replaceChildren();
  data.events.forEach(e=>{const li=document.createElement('li');li.textContent=e.changed_at+' UTC · '+e.question_id+' → '+e.action;events.append(li);});
  draw();
 }
 function draw(){
  const root=$('futterList');root.replaceChildren();
  const term=$('futterSearch').value.toLowerCase();const filter=$('futterFilter').value;
  const shown=items.filter(q=>(filter==='all'||q.status===filter)&&[q.id,q.prompt,q.topic,q.module,q.source].join(' ').toLowerCase().includes(term));
  $('futterCount').textContent=shown.length+' / '+items.length+' Aufgaben';
  shown.forEach(q=>{
   const card=document.createElement('article');card.className='futter-item';
   const heading=document.createElement('h3');heading.textContent=q.prompt;
   const meta=document.createElement('p');meta.className='futter-meta';meta.textContent=q.topic+' · '+q.module+' · '+q.id+' · '+q.status;
   const date=document.createElement('p');date.className='futter-meta';date.textContent='Erfasst: '+(q.created_at?new Date(q.created_at).toLocaleString('de-DE'):'Unbekannt')+' · '+(q.source||'Ohne Herkunft');
   const details=document.createElement('details');const summary=document.createElement('summary');summary.textContent='Lösung prüfen';details.append(summary);
   const list=document.createElement('ol');q.options.forEach((a,i)=>{const li=document.createElement('li');li.textContent=a+(i===q.correct?' ✓':'');list.append(li);});
   const explanation=document.createElement('p');explanation.textContent=q.explanation;details.append(list,explanation);
   const actions=document.createElement('div');actions.className='futter-actions';
   for(const [status,label] of [['active','Aktivieren'],['disabled','Deaktivieren'],['archived','Archivieren']]){
    if(q.status===status)continue;const b=document.createElement('button');b.textContent=label;b.type='button';
    b.addEventListener('click',async()=>{
     if(status==='archived'&&!confirm('Archivieren? Gespeicherte XP bleiben erhalten.'))return;
     b.disabled=true;try{await request('futter_status',{question_id:q.id,status});await refresh();}catch(e){$('futterMessage').textContent=e.message;b.disabled=false;}
    });actions.append(b);
   }
   card.append(heading,meta,date,details,actions);root.append(card);
  });
 }
 $('futterSearch').addEventListener('input',draw);$('futterFilter').addEventListener('change',draw);
 let visibilityLoaded=false;
 const visibilityTimer=setInterval(async()=>{
  if($('dashboard').classList.contains('hidden')||visibilityLoaded)return;
  visibilityLoaded=true;clearInterval(visibilityTimer);
  try{
   const result=await request('futter_public');const hidden=new Set(result.hidden);
   for(const module of pneumaticModules)module.questions.splice(0,module.questions.length,...module.questions.filter(q=>!hidden.has(q.id)));
   electroQuestions.splice(0,electroQuestions.length,...electroQuestions.filter(q=>!hidden.has(q.id)));
   document.querySelectorAll('#pneuModules .exercise-card').forEach((button,i)=>{
    const small=button.querySelector('small');if(small&&pneumaticModules[i])small.textContent=pneumaticModules[i].questions.length+' aktive Aufgaben · Gespeicherter Fortschritt';
   });
  }catch(e){console.error('Aufgabenstatus konnte nicht geladen werden',e);}
 },100);
 const timer=setInterval(async()=>{
  if(!$('adminPanel').classList.contains('hidden')){
   clearInterval(timer);$('futterPanel').classList.remove('hidden');try{await refresh();}catch(e){$('futterMessage').textContent=e.message;}
  }
 },250);
});
