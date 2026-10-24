/* Shared admin question editor: Futter and quiz pencil. */
(()=>{
 let dialog=null;
 async function request(action,data,id){
  const session=await fetch('api.php?action=session',{credentials:'same-origin'}).then(r=>r.json());
  if(session.user?.role!=='admin')throw Error('Nur Administratoren.');
  const url='api.php?action='+action+(id?'&id='+encodeURIComponent(id):'');
  const response=await fetch(url,{method:data?'POST':'GET',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf||''},body:data?JSON.stringify(data):undefined});
  const result=await response.json();if(!response.ok)throw Error(result.error||'Fehler');return result;
 }
 function node(tag,className,text){const n=document.createElement(tag);if(className)n.className=className;if(text!==undefined)n.textContent=text;return n;}
 function field(parent,label,value,rows=0){const wrap=node('label','question-editor-field');wrap.append(node('span','',label));const input=node(rows?'textarea':'input');if(rows)input.rows=rows;input.value=value||'';wrap.append(input);parent.append(wrap);return input;}
 function assetRow(parent,asset={}){
  const row=node('div','question-editor-asset');
  const placement=node('select');for(const [v,t] of [['prompt','Aufgabe'],['solution','Lösung']]){const option=node('option','',t);option.value=v;placement.append(option);}placement.value=asset.placement||'prompt';
  const path=field(row,'Bildpfad (public/assets/… ohne public/) ',asset.path);
  const alt=field(row,'Alternativtext',asset.alt);
  const caption=field(row,'Bildunterschrift',asset.caption);
  const remove=node('button','question-editor-remove','Bild entfernen');remove.type='button';remove.addEventListener('click',()=>row.remove());
  row.prepend(placement);row.append(remove);parent.append(row);
  return ()=>row.isConnected?({placement:placement.value,path:path.value.trim(),alt:alt.value.trim(),caption:caption.value.trim()}):null;
 }
 async function open(id){
  if(dialog)dialog.remove();
  dialog=node('dialog','question-editor-dialog');dialog.setAttribute('aria-label','Aufgabe bearbeiten');
  const form=node('form','question-editor-form');form.addEventListener('submit',e=>e.preventDefault());
  const heading=node('h2','','Aufgabe bearbeiten');
  const message=node('p','question-editor-message','Lade Aufgabe …');
  const close=node('button','question-editor-close','Schließen');close.type='button';close.addEventListener('click',()=>dialog.close());
  form.append(heading,message,close);dialog.append(form);document.body.append(dialog);dialog.showModal();
  try{
   const {question:q}=await request('admin_question_get',null,id);
   message.textContent='Aufgaben-ID: '+q.id+' · Version '+q.version;
   const prompt=field(form,'Aufgabenstellung',q.prompt,5);
   const explanation=field(form,'Lösung und Erklärung',q.solution_explanation,5);
   const hintTitle=node('h3','','Joker (optional)');form.append(hintTitle);
   const hints=q.config?.hints||{};
   const symbols=field(form,'Formelzeichen und Bedeutungen (z. B. d = Durchmesser (mm))',hints.symbols,3);
   const text=field(form,'Texttipp',hints.text,3);
   const book=field(form,'Buchtipp (nur mit überprüfter Seitenangabe)',hints.book,2);
   const assetTitle=node('h3','','Bilder');form.append(assetTitle);
   const assetContainer=node('div','question-editor-assets');form.append(assetContainer);
   const readers=[];for(const asset of q.assets||[])readers.push(assetRow(assetContainer,asset));
   const add=node('button','question-editor-add','+ Bildpfad hinzufügen');add.type='button';add.addEventListener('click',()=>readers.push(assetRow(assetContainer)));
   form.append(add);
   const note=node('p','question-editor-note','Bilder müssen bereits unter public/assets/ auf dem Server liegen. Der Bildpfad wird hier zugeordnet.');
   form.append(note);
   const save=node('button','question-editor-save','Änderungen speichern');save.type='button';
   save.addEventListener('click',async()=>{
    save.disabled=true;message.textContent='Speichere …';
    try{
     const assets=readers.map(read=>read()).filter(a=>a&&a.path);
     await request('admin_question_save',{id:q.id,version:q.version,prompt:prompt.value,explanation:explanation.value,hints:{symbols:symbols.value,text:text.value,book:book.value},assets});
     dialog.close();location.reload();
    }catch(e){message.textContent=e.message;save.disabled=false;}
   });
   form.append(save);
  }catch(e){message.textContent=e.message;}
 }
 window.MechaQuestionEditor={open};
 document.addEventListener('click',event=>{const button=event.target.closest('[data-edit-question]');if(button){event.preventDefault();open(button.dataset.editQuestion);}});
})();