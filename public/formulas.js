document.addEventListener('DOMContentLoaded',()=>{
 const $=id=>document.getElementById(id);
 const topics=JSON.parse($('formulaData').textContent).topics;
 let deck=[],index=0,correct=0,answered=false;
 const shuffle=a=>{const b=[...a];for(let i=b.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[b[i],b[j]]=[b[j],b[i]];}return b;};
 function start(){
  const topic=$('formulaTopic').value;
  deck=shuffle(topics.filter(t=>topic==='all'||t.id===topic).flatMap(t=>t.formulas.map((f,i)=>({id:t.id+'-'+i,topic:t.title,name:f[0],answer:f[1],options:f[2]}))));
  index=0;correct=0;show();
 }
 function normalize(s){return s.toLowerCase().replace(/\s+/g,'').replace(/\*/g,'·').replace(/\^2/g,'²').replace(/\bpi\b/g,'π').replace(/ges/g,'ges');}
 function show(){
  const root=$('formulaQuestion');root.replaceChildren();$('formulaFeedback').textContent='';$('formulaNext').classList.add('hidden');answered=false;
  if(index>=deck.length){$('formulaProgress').textContent='Runde beendet · '+correct+' von '+deck.length+' richtig';const end=document.createElement('p');end.textContent='Du kannst direkt eine neue Runde starten.';root.append(end);return;}
  const f=deck[index];$('formulaProgress').textContent=(index+1)+' / '+deck.length+' · '+correct+' richtig';
  const card=document.createElement('div');card.className='formula-card';const sub=document.createElement('p');sub.textContent=f.topic;const title=document.createElement('h3');title.textContent=f.name;card.append(sub,title);
  function grade(ok){if(answered)return;answered=true;if(ok)correct++;$('formulaFeedback').textContent=(ok?'✓ Richtig!':'✕ Noch nicht richtig.')+' · '+f.answer;$('formulaNext').classList.remove('hidden');card.querySelectorAll('button,input').forEach(el=>el.disabled=true);}
  if($('formulaType').value==='choice'){
   const opts=document.createElement('div');opts.className='formula-options';
   shuffle(f.options).forEach(value=>{const b=document.createElement('button');b.type='button';b.textContent=value;b.addEventListener('click',()=>grade(value===f.answer));opts.append(b);});card.append(opts);
  }else{
   const form=document.createElement('form');const label=document.createElement('label');label.textContent='Formel eingeben';const input=document.createElement('input');input.required=true;input.autocomplete='off';input.placeholder='z. B. U = R · I';label.append(input);const button=document.createElement('button');button.textContent='Prüfen';form.append(label,button);
   form.addEventListener('submit',e=>{e.preventDefault();grade(normalize(input.value)===normalize(f.answer));});card.append(form);
  }
  root.append(card);
 }
 $('formulaOpen').addEventListener('click',()=>{['dashboard','topicPanel','metalPanel','pneuPanel','appPanel'].forEach(id=>$(id).classList.add('hidden'));$('formulaPanel').classList.remove('hidden');start();});
 $('formulaBack').addEventListener('click',()=>{$('formulaPanel').classList.add('hidden');$('dashboard').classList.remove('hidden');});
 $('formulaStart').addEventListener('click',start);
 $('formulaNext').addEventListener('click',()=>{index++;show();});
});
