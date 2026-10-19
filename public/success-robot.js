// Kurzes Erfolgsbild; bei Fehlern ein erklärender Dialog mit bewusster Bestätigung.
(()=>{
 let timer,continueAfterError=null,previousFocus=null;
 const popup=document.createElement('div');popup.className='success-robot-popup';popup.setAttribute('aria-hidden','true');
 const plate=document.createElement('div');plate.className='success-robot-plate';
 const img=document.createElement('img');img.src='img/roboter-bleistiftskizze-popup.png';img.alt='';img.width=220;img.height=220;
 const explanation=document.createElement('div');explanation.className='robot-explanation';explanation.hidden=true;
 const heading=document.createElement('h3');heading.textContent='Noch nicht richtig';
 const detail=document.createElement('p');
 const ok=document.createElement('button');ok.type='button';ok.textContent='OK – nächste Aufgabe';
 explanation.append(heading,detail,ok);plate.append(img,explanation);popup.append(plate);document.body.append(popup);
 function hide(){popup.classList.remove('is-visible','is-wrong','is-dialog');popup.setAttribute('aria-hidden','true');explanation.hidden=true;document.removeEventListener('keydown',trap);if(previousFocus?.isConnected)previousFocus.focus();}
 function trap(e){if(e.key==='Tab'){e.preventDefault();ok.focus();}}
 ok.addEventListener('click',()=>{const next=continueAfterError;continueAfterError=null;hide();if(typeof next==='function')next();});
 document.addEventListener('mechatraining:correct',()=>{
  clearTimeout(timer);continueAfterError=null;explanation.hidden=true;popup.classList.remove('is-wrong','is-dialog','is-visible');img.src='img/roboter-bleistiftskizze-popup.png';popup.setAttribute('aria-hidden','true');void popup.offsetWidth;popup.classList.add('is-visible');timer=setTimeout(hide,1050);
 });
 document.addEventListener('mechatraining:wrong',event=>{
  clearTimeout(timer);previousFocus=document.activeElement;continueAfterError=event.detail?.onConfirm||null;
  detail.textContent=event.detail?.explanation||'Die richtige Lösung wird bei der Aufgabe angezeigt.';
  img.src='img/roboter-fehler-popup.png';explanation.hidden=false;popup.classList.remove('is-visible');popup.classList.add('is-wrong','is-dialog');popup.setAttribute('aria-hidden','false');void popup.offsetWidth;popup.classList.add('is-visible');document.addEventListener('keydown',trap);ok.focus();
 });
})();
