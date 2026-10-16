'use strict';
(()=>{
 const toggle=document.getElementById('notesToggle'),panel=document.getElementById('notesPanel'),backdrop=document.getElementById('notesBackdrop'),field=document.getElementById('notesText'),status=document.getElementById('notesStatus');
 const key='mechatraining.notes.v1';let lastFocus=null;
 try{field.value=localStorage.getItem(key)||'';}catch{status.textContent='Lokales Speichern nicht verfügbar';}
 function open(value){if(value)lastFocus=document.activeElement;panel.classList.toggle('open',value);panel.inert=!value;panel.setAttribute('aria-hidden',String(!value));toggle.setAttribute('aria-expanded',String(value));backdrop.classList.toggle('hidden',!value);document.body.style.overflow=value?'hidden':'';if(value)field.focus();else if(lastFocus&&lastFocus.isConnected)lastFocus.focus();}
 toggle.addEventListener('click',()=>open(true));document.getElementById('notesClose').addEventListener('click',()=>open(false));backdrop.addEventListener('click',()=>open(false));
 field.addEventListener('input',()=>{try{localStorage.setItem(key,field.value);status.textContent='Automatisch gespeichert';}catch{status.textContent='Speichern fehlgeschlagen – bitte als TXT sichern';}});
 document.getElementById('notesExport').addEventListener('click',()=>{const blob=new Blob([field.value],{type:'text/plain;charset=utf-8'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download='mechatraining-notizen.txt';document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&panel.classList.contains('open')){e.preventDefault();open(false);}});
})();
