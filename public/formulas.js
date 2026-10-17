document.addEventListener('DOMContentLoaded',()=>{
 const $=id=>document.getElementById(id);
 const topics=JSON.parse($('formulaData').textContent).topics;
 let deck=[],index=0,correct=0,answered=false,streak=0,ladderLevel=0;
 const saveKey=()=> 'mechatraining-formula-level:'+($('username').textContent||'guest');
 function loadLevel(){try{const v=JSON.parse(localStorage.getItem(saveKey())||'{}');ladderLevel=Math.max(0,Number(v.level)||0);streak=Math.max(0,Math.min(4,Number(v.streak)||0));}catch(e){ladderLevel=0;streak=0;}}
 function saveLevel(){try{localStorage.setItem(saveKey(),JSON.stringify({level:ladderLevel,streak}));}catch(e){/* Browser-Speicher kann deaktiviert sein */}}
 const symbols={U:'Elektrische Spannung (Volt, V)',I:'Elektrische Stromstärke (Ampere, A)',R:'Elektrischer Widerstand (Ohm, Ω)',P:'Elektrische Leistung (Watt, W)',G:'Elektrischer Leitwert (Siemens, S)',V:'Volumen (z. B. mm³ oder cm³)',A:'Kolben- bzw. Querschnittsfläche (z. B. mm²)',s:'Hubweg / zurückgelegter Weg (z. B. mm)',p:'Druck (Pascal oder bar)',F:'Kraft (Newton, N)',D:'Kolbendurchmesser (z. B. mm)',d:'Kolbenstangendurchmesser (z. B. mm)',AR:'Ringfläche auf der Stangenseite (z. B. mm²)',Faus:'Ausfahrkraft (Newton, N)',Fein:'Einfahrkraft (Newton, N)',Rges:'Gesamtwiderstand (Ohm, Ω)',R1:'Widerstand 1 (Ohm, Ω)',R2:'Widerstand 2 (Ohm, Ω)',Izu:'Zufließender Strom (Ampere, A)',Iab:'Abfließender Strom (Ampere, A)','π':'Kreiszahl Pi (ungefähr 3,1416)','Σ':'Summenzeichen: alle betreffenden Werte addieren'};
 function formulaSymbols(answer){const found=[...new Set((answer.match(/Faus|Fein|Rges|Izu|Iab|AR|R[12]|[UIPRGVAs pFDdΣπ]/g)||[]).filter(x=>x.trim()&&symbols[x]))];return found.map(x=>[x,symbols[x]]);}
 const shuffle=a=>{const b=[...a];for(let i=b.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[b[i],b[j]]=[b[j],b[i]];}return b;};
 function start(){
  const topic=$('formulaTopic').value;
  deck=shuffle(topics.filter(t=>topic==='all'||t.id===topic).flatMap(t=>t.formulas.map((f,i)=>({id:t.id+'-'+i,topic:t.title,name:f[0],answer:f[1],options:f[2]}))));
  index=0;correct=0;loadLevel();$('formulaClimbMessage').textContent=' ';updateLadder();show();
 }
 function updateLadder(){const el=$('formulaClimber');$('formulaClimbLevel').textContent='Level '+ladderLevel;$('formulaClimbStreak').textContent=streak+' / 5';el.style.left=(8+streak*21)+'%';const bars=$('formulaLevelBars');bars.replaceChildren();for(let i=0;i<ladderLevel;i++){const bar=document.createElement('span');bar.className='formula-level-mark';bars.append(bar);}bars.setAttribute('aria-label',ladderLevel+' erreichte Level');}
 function normalize(s){return s.toLowerCase().replace(/\s+/g,'').replace(/\*/g,'·').replace(/\^2/g,'²').replace(/\bpi\b/g,'π').replace(/ges/g,'ges');}
 function show(){
  const root=$('formulaQuestion');root.replaceChildren();$('formulaFeedback').textContent='';$('formulaNext').classList.add('hidden');answered=false;
  if(index>=deck.length){$('formulaProgress').textContent='Runde beendet · '+correct+' von '+deck.length+' richtig';const end=document.createElement('p');end.textContent='Du kannst direkt eine neue Runde starten.';root.append(end);return;}
  const f=deck[index];$('formulaProgress').textContent=(index+1)+' / '+deck.length+' · '+correct+' richtig';
  const card=document.createElement('div');card.className='formula-card';const sub=document.createElement('p');sub.textContent=f.topic;const title=document.createElement('h3');title.textContent=f.name;card.append(sub,title);const definitions=formulaSymbols(f.answer);if(definitions.length){const info=document.createElement('details');info.className='formula-info';const trigger=document.createElement('summary');trigger.textContent='ⓘ';trigger.setAttribute('aria-label','Formelzeichen erklären');trigger.title='Formelzeichen erklären';const content=document.createElement('dl');for(const [symbol,meaning] of definitions){const term=document.createElement('dt');term.textContent=symbol;const desc=document.createElement('dd');desc.textContent=meaning;content.append(term,desc);}info.append(trigger,content);title.append(info);}
  function grade(ok){if(answered)return;answered=true;if(ok){correct++;streak++;if(streak===5){streak=0;ladderLevel++;$('formulaClimbMessage').textContent='Level '+ladderLevel+' erreicht!';}}else{streak=0;$('formulaClimbMessage').textContent='';}saveLevel();updateLadder();$('formulaFeedback').textContent=(ok?'✓ Richtig!':'✕ Noch nicht richtig.')+' · '+f.answer;$('formulaNext').classList.remove('hidden');card.querySelectorAll('button,input').forEach(el=>el.disabled=true);}
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
