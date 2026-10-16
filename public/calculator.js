'use strict';
(()=>{
 const panel=document.getElementById('calcPanel'),toggle=document.getElementById('calcToggle'),backdrop=document.getElementById('calcBackdrop');
 const display=document.getElementById('calcDisplay'),expression=document.getElementById('calcExpression'),historyEl=document.getElementById('calcHistory');
 let input='',answer=0,memory=0,angle='DEG',history=[],fresh=false;
 const funcs={sin:x=>Math.sin(toRad(x)),cos:x=>Math.cos(toRad(x)),tan:x=>Math.tan(toRad(x)),asin:x=>fromRad(Math.asin(x)),acos:x=>fromRad(Math.acos(x)),atan:x=>fromRad(Math.atan(x)),sqrt:Math.sqrt,ln:Math.log,log:Math.log10,abs:Math.abs,exp:Math.exp};
 const toRad=x=>angle==='DEG'?x*Math.PI/180:x,fromRad=x=>angle==='DEG'?x*180/Math.PI:x;
 const fmt=x=>{if(!Number.isFinite(x))throw Error('Undefiniertes Ergebnis');if(Math.abs(x)<1e-12)x=0;return Number(x.toPrecision(12)).toString().replace('.',',');};
 function evaluate(source){
  const tokens=source.replace(/,/g,'.').match(/(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?|[a-zA-Z]+|[()+*/^!%\-]/g)||[];
  if(tokens.join('')!==source.replace(/\s+/g,'').replace(/,/g,'.'))throw Error('Ungültiger Ausdruck');
  let i=0;const peek=()=>tokens[i],take=()=>tokens[i++];
  function primary(){const t=take();if(t===undefined)throw Error('Unvollständiger Ausdruck');if(t==='('){const v=sum();if(take()!==')')throw Error('Klammer fehlt');return v;}
   if(t==='pi')return Math.PI;if(t==='e')return Math.E;if(t==='ANS')return answer;
   if(Object.hasOwn(funcs,t)){if(take()!=='(')throw Error('Klammer fehlt');const v=sum();if(take()!==')')throw Error('Klammer fehlt');return funcs[t](v);}
   if(/^(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/.test(t))return Number(t);
   throw Error('Ungültiger Ausdruck');}
  function power(){let v=primary();while(peek()==='!'||peek()==='%'){const op=take();if(op==='%')v/=100;else{if(!Number.isInteger(v)||v<0||v>170)throw Error('Fakultät nur für 0–170');let n=1;for(let k=2;k<=v;k++)n*=k;v=n;}}if(peek()==='^'){take();v=Math.pow(v,unary());}return v;}
  function unary(){if(peek()==='+'){take();return unary();}if(peek()==='-'){take();return -unary();}return power();}
  function product(){let v=unary();while(peek()==='*'||peek()==='/'){const op=take(),rhs=unary();v=op==='*'?v*rhs:v/rhs;}return v;}
  function sum(){let v=product();while(peek()==='+'||peek()==='-'){const op=take(),rhs=product();v=op==='+'?v+rhs:v-rhs;}return v;}
  const result=sum();if(i!==tokens.length)throw Error('Ungültiger Ausdruck');if(!Number.isFinite(result))throw Error('Undefiniertes Ergebnis');return result;
 }
 function render(){expression.textContent=input||'Bereit';display.textContent=input?input.replaceAll('*','×').replaceAll('/','÷').replaceAll('pi','π').replaceAll('.',','):'0';}
 function renderHistory(){historyEl.replaceChildren();history.forEach(item=>{const b=document.createElement('button');const small=document.createElement('small');small.textContent=item.input;b.append(small,document.createTextNode(item.value));b.addEventListener('click',()=>{input=item.value.replace(',','.');fresh=false;render();});historyEl.append(b);});}
 function action(key){
  if(key==='AC'){input='';fresh=false;render();return;}
  if(key==='DEL'){input=input.slice(0,-1);fresh=false;render();return;}
  if(key==='MC'){memory=0;return;}
  if(key==='MR'){input+=String(memory);render();return;}
  if(key==='M+'){try{memory+=input?evaluate(input):answer;display.textContent=fmt(memory);}catch(e){display.textContent=e.message;}return;}
  if(key==='='){if(!input)return;try{const result=evaluate(input),value=fmt(result);history.unshift({input,value});history=history.slice(0,15);answer=result;expression.textContent=input.replaceAll('*','×').replaceAll('/','÷');display.textContent=value;input=String(result);fresh=true;renderHistory();}catch(e){display.textContent=e.message;}return;}
  if(key==='ANS')key='ANS';
  if(fresh&&/^[0-9.(]|^(?:sin|cos|tan|asin|acos|atan|sqrt|ln|log|pi|e|ANS)/.test(key))input='';
  fresh=false;input+=key;render();
 }
 function setOpen(open){panel.classList.toggle('open',open);panel.inert=!open;panel.setAttribute('aria-hidden',String(!open));toggle.setAttribute('aria-expanded',String(open));backdrop.classList.toggle('hidden',!open);document.body.style.overflow=open?'hidden':'';if(open)panel.querySelector('[data-calc]').focus();else toggle.focus();}
 toggle.addEventListener('click',()=>setOpen(true));document.getElementById('calcClose').addEventListener('click',()=>setOpen(false));backdrop.addEventListener('click',()=>setOpen(false));
 panel.querySelectorAll('[data-calc]').forEach(b=>b.addEventListener('click',()=>action(b.dataset.calc)));
 document.getElementById('calcAngle').addEventListener('click',e=>{angle=angle==='DEG'?'RAD':'DEG';e.currentTarget.textContent=angle;});
 document.getElementById('calcClearHistory').addEventListener('click',()=>{history=[];renderHistory();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&panel.classList.contains('open')){e.preventDefault();setOpen(false);return;}if(!panel.classList.contains('open')||e.ctrlKey||e.altKey||e.metaKey)return;const target=e.target;if(target instanceof HTMLInputElement||target instanceof HTMLTextAreaElement)return;let key=e.key;if(key==='Enter'||key==='=')key='=';else if(key==='Backspace')key='DEL';else if(key==='Delete')key='AC';else if(key===',')key='.';else if(!/^[0-9+*/^().!%\-]$/.test(key))return;e.preventDefault();action(key);});
 render();
})();
