/* Shared admin question editor: Futter and quiz pencil. */
(()=>{
 let dialog=null;
 async function request(action,data,id){
  const session=await fetch('api.php?action=session',{credentials:'same-origin'}).then(r=>r.json());
  if(!['admin','editor'].includes(session.user?.role))throw Error('Nur Administratoren.');
  const url='api.php?action='+action+(id?'&id='+encodeURIComponent(id):'');
  const response=await fetch(url,{method:data?'POST':'GET',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf||''},body:data?JSON.stringify(data):undefined});
  const result=await response.json();if(!response.ok)throw Error(result.error||'Fehler');return result;
 }
 function node(tag,className,text){const n=document.createElement(tag);if(className)n.className=className;if(text!==undefined)n.textContent=text;return n;}
 function field(parent,label,value,rows=0){const wrap=node('label','question-editor-field');wrap.append(node('span','',label));const input=node(rows?'textarea':'input');if(rows)input.rows=rows;input.value=value||'';wrap.append(input);parent.append(wrap);return input;}
 function assetRow(parent,asset={}){
  const row=node('div','question-editor-asset');
  const placement=node('select');for(const [v,t] of [['prompt','Aufgabe'],['solution','Lösung']]){const option=node('option','',t);option.value=v;placement.append(option);}placement.value=asset.placement||'prompt';
  const path=field(row,'Dateipfad',asset.path);
  const preview=node('img','question-editor-preview');preview.alt='Bildvorschau';preview.loading='lazy';
   function updatePreview(){const value=path.value.trim();if(value.startsWith('assets/') && /\.(png|jpe?g|webp|svg)$/i.test(value) && !value.includes('..')){preview.src=value;preview.hidden=false;}else{preview.removeAttribute('src');preview.hidden=true;}}
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
  const libraryButton=node('button','question-editor-library-button','Aus Bibliothek wählen');libraryButton.type='button';
  libraryButton.addEventListener('click',async()=>{
   libraryButton.disabled=true;
   let chooser=null;
   try{
    const data=await request('admin_image_library');
    chooser=node('div','question-editor-library');
    const heading=node('strong','','Bilderbibliothek');chooser.append(heading);
    const grid=node('div','question-editor-library-grid');
    for(const image of data.images||[]){
     const pick=node('button','question-editor-library-item');pick.type='button';pick.title=image.path;
     const thumb=node('img');thumb.src=image.path;thumb.alt=image.name;thumb.loading='lazy';
     pick.append(thumb,node('span','',image.name));
     pick.addEventListener('click',()=>{path.value=image.path;updatePreview();chooser.remove();libraryButton.disabled=false;});
     grid.append(pick);
    }
    if(!grid.childElementCount)chooser.append(node('p','','Noch keine Bilder vorhanden. Lade zunächst ein Bild hoch.'));
    chooser.append(grid);
    const cancel=node('button','','Schließen');cancel.type='button';cancel.addEventListener('click',()=>{chooser.remove();libraryButton.disabled=false;});chooser.append(cancel);
    row.append(chooser);
   }catch(error){alert(error.message);libraryButton.disabled=false;}
  });
  row.append(upload,uploadButton,libraryButton);
  const pdfUpload=node('input','question-editor-upload');pdfUpload.type='file';pdfUpload.accept='application/pdf';
  const pdfButton=node('button','question-editor-upload-button','PDF hochladen');pdfButton.type='button';pdfButton.addEventListener('click',()=>pdfUpload.click());
  pdfUpload.addEventListener('change',async()=>{
   const file=pdfUpload.files?.[0];if(!file)return;pdfButton.disabled=true;
   try{const session=await fetch('api.php?action=session',{credentials:'same-origin'}).then(r=>r.json());
    const data=new FormData();data.append('question_id',window.currentEditedQuestionId);data.append('document',file);
    const response=await fetch('api.php?action=admin_document_upload',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':session.csrf||''},body:data});
    const result=await response.json();if(!response.ok)throw Error(result.error||'Upload fehlgeschlagen');path.value=result.path;updatePreview();
   }catch(e){alert(e.message);}finally{pdfButton.disabled=false;pdfUpload.value='';}
  });
  row.append(pdfUpload,pdfButton);
  const alt=field(row,'Alternativtext / Dokumenttitel',asset.alt);
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
   if(q.type==='matching'){
    const heading=node('h3','','Nummerierte Begriffe');form.append(heading);
    const list=node('div','question-editor-matching-list');form.append(list);
    const entries=[];
    function appendTerm(value=''){
      const row=node('div','question-editor-matching-row');
      const number=node('strong');const input=node('input');input.value=value;input.maxLength=100;
      const remove=node('button','','Entfernen');remove.type='button';
      const entry={row,number,input};entries.push(entry);
      const renumber=()=>entries.forEach((e,i)=>e.number.textContent=(i+1)+'.');
      remove.addEventListener('click',()=>{if(entries.length<=2)return;entries.splice(entries.indexOf(entry),1);row.remove();renumber();});
      row.append(number,input,remove);list.append(row);renumber();
    }
    (q.solution?.answers||[]).forEach(appendTerm);
    const addTerm=node('button','','+ Begriff');addTerm.type='button';addTerm.addEventListener('click',()=>{if(entries.length<30)appendTerm();});form.append(addTerm);
    solutionInputs.matching=()=>entries.map(e=>e.input.value.trim());
   }
   const hintTitle=node('h3','','Joker (optional)');form.append(hintTitle);
   const hints=q.config?.hints||{};
   const symbols=field(form,'Formelzeichen und Bedeutungen (z. B. d = Durchmesser (mm))',hints.symbols,3);
   const text=field(form,'Texttipp',hints.text,3);
   const book=field(form,'Buchtipp (nur mit überprüfter Seitenangabe)',hints.book,2);
   const formula=field(form,'Formel-Joker (wird nur auf Wunsch eingeblendet)',hints.formula|| (q.id==='ap1-metall-bohren-test-001'?'n = (1000 × v_c) / (π × d)':''),3);
   const assetTitle=node('h3','','Bilder und PDF-Dokumente');form.append(assetTitle);
   const assetContainer=node('div','question-editor-assets');form.append(assetContainer);
   const readers=[];for(const asset of q.assets||[])readers.push(assetRow(assetContainer,asset));
   const add=node('button','question-editor-add','+ Bild oder Dokument hinzufügen');add.type='button';add.addEventListener('click',()=>readers.push(assetRow(assetContainer)));
   form.append(add);
   const note=node('p','question-editor-note','PNG, JPG oder WebP bis 8 MB; PDF bis 10 MB. Vorhandene Bilder kannst du aus der Bibliothek mehrfach verwenden. Anschließend Änderungen speichern.');
   form.append(note);
   const save=node('button','question-editor-save','Änderungen speichern');save.type='button';
   save.addEventListener('click',async()=>{
    save.disabled=true;message.textContent='Speichere …';
    try{
     const assets=readers.map(read=>read()).filter(a=>a&&a.path);
     const solution=q.type==='multiple_choice'?{options:solutionInputs.options.map(input=>input.value),correct:Number(solutionInputs.correct.value)}:q.type==='numeric'?{value:solutionInputs.value.value.replace(',','.'),tolerance:solutionInputs.tolerance.value.replace(',','.')}:(q.type==='text_input'?{accepted:solutionInputs.accepted.value.split('\\n').map(v=>v.trim()).filter(Boolean)}:q.type==='matching'?{answers:solutionInputs.matching()}:null);
     await request('admin_question_save',{id:q.id,version:q.version,prompt:prompt.value,explanation:explanation.value,solution,hints:{symbols:symbols.value,text:text.value,book:book.value,formula:formula.value},assets});
     dialog.close();location.reload();
    }catch(e){message.textContent=e.message;save.disabled=false;}
   });
   form.append(save);
  }catch(e){message.textContent=e.message;}
 }
 async function create(defaultModule=''){
  if(dialog)dialog.remove();
  dialog=node('dialog','question-editor-dialog');dialog.setAttribute('aria-label','Neue Quiz-Aufgabe');
  const form=node('form','question-editor-form');form.addEventListener('submit',e=>e.preventDefault());
  form.append(node('h2','','Neue Quiz-Aufgabe'));
  const message=node('p','question-editor-message','');
  const close=node('button','','Schließen');close.type='button';close.addEventListener('click',()=>dialog.close());form.append(message,close);
  dialog.append(form);document.body.append(dialog);dialog.showModal();
  try{
   const data=await request('admin_question_modules');
   const moduleSelect=node('select');
   for(const module of data.modules||[]){const option=node('option','',module.subject+' / '+module.title);option.value=module.id;moduleSelect.append(option);}
   if(defaultModule && [...moduleSelect.options].some(o=>o.value===defaultModule))moduleSelect.value=defaultModule;
   const moduleLabel=node('label','question-editor-field');moduleLabel.append(node('span','','Modul'),moduleSelect);form.append(moduleLabel);
   const typeSelect=node('select');for(const [value,label] of [['multiple_choice','Auswahlfrage'],['numeric','Zahlenaufgabe'],['text_input','Texteingabe'],['matching','Nummerierte Zuordnung']]){const option=node('option','',label);option.value=value;typeSelect.append(option);}
   const typeLabel=node('label','question-editor-field');typeLabel.append(node('span','','Aufgabentyp'),typeSelect);form.append(typeLabel);
   const prompt=field(form,'Aufgabenstellung','',4);
   const explanation=field(form,'Lösung und Erklärung','',3);
   const specifics=node('div','question-editor-new-fields');form.append(specifics);
   const controls={};
   function rebuild(){
    specifics.replaceChildren();
    const type=typeSelect.value;
    if(type==='multiple_choice'){
     controls.options=[];for(let i=0;i<4;i++)controls.options.push(field(specifics,'Antwort '+(i+1),''));
     const label=node('label','question-editor-field');label.append(node('span','','Richtige Antwort'));
     controls.correct=node('select');for(let i=0;i<4;i++){const option=node('option','',String(i+1));option.value=String(i);controls.correct.append(option);}label.append(controls.correct);specifics.append(label);
    }else if(type==='numeric'){
     controls.value=field(specifics,'Richtiger Zahlenwert','');
     controls.tolerance=field(specifics,'Toleranz','0');
    }else if(type==='text_input')controls.accepted=field(specifics,'Akzeptierte Antworten (je Zeile eine)','',4);
    else controls.answers=field(specifics,'Richtige Begriffe (je Nummer eine Zeile)','',8);
   }
   typeSelect.addEventListener('change',rebuild);rebuild();
   const save=node('button','question-editor-save','Aufgabe anlegen');save.type='button';
   save.addEventListener('click',async()=>{
    save.disabled=true;message.textContent='Speichere …';
    try{
     const payload={module_id:moduleSelect.value,type:typeSelect.value,prompt:prompt.value,explanation:explanation.value};
     if(payload.type==='multiple_choice'){payload.options=controls.options.map(x=>x.value);payload.correct=Number(controls.correct.value);}
     if(payload.type==='numeric'){payload.value=controls.value.value.replace(',','.');payload.tolerance=controls.tolerance.value.replace(',','.');}
     if(payload.type==='text_input')payload.accepted=controls.accepted.value.split(/\\r?\\n/).map(x=>x.trim()).filter(Boolean);
     if(payload.type==='matching')payload.answers=controls.answers.value.split(/\\r?\\n/).map(x=>x.trim()).filter(Boolean);
     const result=await request('admin_question_create',payload);
     message.textContent='Angelegt: '+result.id;location.reload();
    }catch(error){message.textContent=error.message;save.disabled=false;}
   });form.append(save);
  }catch(error){message.textContent=error.message;}
 }
 window.MechaQuestionEditor={open,create};
 document.addEventListener('click',event=>{const button=event.target.closest('[data-edit-question]');if(button){event.preventDefault();open(button.dataset.editQuestion);}});
})();