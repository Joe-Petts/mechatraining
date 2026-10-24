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
  const path=field(row,'Bildpfad',asset.path);
  const preview=node('img','question-editor-preview');preview.alt='Bildvorschau';preview.loading='lazy';
  function updatePreview(){const value=path.value.trim();if(/^assets\\/[a-zA-Z0-9/_-]+\\.(png|jpe?g|webp|svg)$/.test(value)){preview.src=value;preview.hidden=false;}else{preview.removeAttribute('src');preview.hidden=true;}}
  path.addEventListener('input',updatePreview);updatePreview();row.append(preview);
  const upload=node('input','question-editor-upload');upload.type='file';upload.accept='image/png,image/jpeg,image/webp';
  const uploadButton=node('button','question-editor-upload-button','Bild hochladen');uploadButton.type='button';
  uploadButton.addEventListener('click',()=>upload.click());
  upload.addEventListener('change',async()=>{
   const file=upload.files?.[0];if(!file)return;
   uploadButton.disabled=true;uploadButton.textContent='Lade hoch …';
   try{
    const session=await fetch('api.php?action=session',{credentials:'same-origin'}).then(r=>r.json());
    if(session.user?.role!=='admin')throw Error('Keine Berechtigung.');
    const data=new FormData();data.append('question_id',window.currentEditedQuestionId);data.append('image',file);
    const response=await fetch('api.php?action=admin_question_upload',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':session.csrf||''},body:data});
    const result=await response.json();if(!response.ok)throw Error(result.error||'Upload fehlgeschlagen.');
    path.value=result.path;updatePreview();
   }catch(e){alert(e.message);}
   finally{uploadButton.disabled=false;uploadButton.textContent='Bild hochladen';upload.value='';}
  });
  row.append(upload,uploadButton);
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
   const {question:q}=await request('admin_question_get',null,id);window.currentEditedQuestionId=q.id;
   message.textContent='Aufgaben-ID: '+q.id+' · Version '+q.version;
   const prompt=field(form,'Aufgabenstellung',q.prompt,5);
   const explanation=field(form,'Lösung und Erklärung',q.solution_explanation,5);
   const solutionInputs={};
   if(q.type==='multiple_choice'){
    solutionInputs.options=[];
    for(const opt of q.options||[])solutionInputs.options.push(field(form,'Antwort '+(Number(opt.option_index)+1),opt.text));
    const correctWrap=node('label','question-editor-field');correctWrap.append(node('span','','Richtige Antwort'));
    const select=node('select');for(const opt of q.options||[]){const choice=node('option','',String(Number(opt.option_index)+1));choice.value=String(opt.option_index);select.append(choice);}
    select.value=String((q.options||[]).find(o=>Number(o.is_correct)===1)?.option_index??0);
    correctWrap.append(select);form.append(correctWrap);solutionInputs.correct=select;
   }else if(q.type==='numeric'){
    solutionInputs.value=field(form,'Richtiger Zahlenwert',String(q.solution?.value??''));
    solutionInputs.tolerance=field(form,'Zulässige Abweichung',String(q.solution?.tolerance??0));
   }else if(q.type==='text_input'){
    solutionInputs.accepted=field(form,'Akzeptierte Antworten (eine pro Zeile)',(q.solution?.accepted||[]).join('\\n'),3);
   }
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
   const note=node('p','question-editor-note','PNG, JPG oder WebP bis 8 MB. Bild hochladen, Vorschau prüfen und anschließend Änderungen speichern.');
   form.append(note);
   const save=node('button','question-editor-save','Änderungen speichern');save.type='button';
   save.addEventListener('click',async()=>{
    save.disabled=true;message.textContent='Speichere …';
    try{
     const assets=readers.map(read=>read()).filter(a=>a&&a.path);
     const solution=q.type==='multiple_choice'?{options:solutionInputs.options.map(input=>input.value),correct:Number(solutionInputs.correct.value)}:q.type==='numeric'?{value:solutionInputs.value.value.replace(',','.'),tolerance:solutionInputs.tolerance.value.replace(',','.')}:(q.type==='text_input'?{accepted:solutionInputs.accepted.value.split('\\n').map(v=>v.trim()).filter(Boolean)}:null);
     await request('admin_question_save',{id:q.id,version:q.version,prompt:prompt.value,explanation:explanation.value,solution,hints:{symbols:symbols.value,text:text.value,book:book.value},assets});
     dialog.close();location.reload();
    }catch(e){message.textContent=e.message;save.disabled=false;}
   });
   form.append(save);
  }catch(e){message.textContent=e.message;}
 }
 window.MechaQuestionEditor={open};
 document.addEventListener('click',event=>{const button=event.target.closest('[data-edit-question]');if(button){event.preventDefault();open(button.dataset.editQuestion);}});
})();