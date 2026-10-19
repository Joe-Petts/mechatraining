// Kurze, nicht blockierende Erfolgsanimation für beide Lernmodi.
(()=>{
 let timer;
 const popup=document.createElement('div');
 popup.className='success-robot-popup';
 popup.setAttribute('aria-hidden','true');
 const plate=document.createElement('div');plate.className='success-robot-plate';
 const img=document.createElement('img');
 img.src='img/roboter-bleistiftskizze-popup.png';
 img.alt='';
 img.width=220;
 img.height=220;
 plate.append(img);popup.append(plate);
 document.body.append(popup);
 document.addEventListener('mechatraining:correct',()=>{
   clearTimeout(timer);
   popup.classList.remove('is-visible');
   void popup.offsetWidth;
   popup.classList.add('is-visible');
   timer=setTimeout(()=>popup.classList.remove('is-visible'),1050);
 });
})();
