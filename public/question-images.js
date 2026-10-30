'use strict';
window.MechaQuestionImages={
 append(target,question,placement){
  const items=(question.assets||[]).filter(item=>item.placement===placement);
  if(!items.length)return;
  const gallery=document.createElement('div');gallery.className='question-image-gallery';
  for(const item of items){
   const path=String(item.path||'');
   if(!/^assets\/[a-zA-Z0-9/_-]+\.(png|jpe?g|webp|svg|pdf)$/.test(path))continue;
   if(path.endsWith('.pdf')){const link=document.createElement('a');link.href=path;link.target='_blank';link.rel='noopener noreferrer';link.className='question-document-link';link.textContent='📄 '+(item.caption||item.alt||'PDF-Merkblatt öffnen');gallery.append(link);continue;}
   const figure=document.createElement('figure');
   const link=document.createElement('a');link.href=path;link.target='_blank';link.rel='noopener noreferrer';link.title='Zeichnung vergrößern';
   const img=document.createElement('img');img.src=path;img.alt=item.alt||item.caption||'Technische Zeichnung';img.loading='lazy';
   link.append(img);figure.append(link);
   if(item.caption && !/^Gegeben\s*:/i.test(item.caption)){const caption=document.createElement('figcaption');caption.textContent=item.caption;figure.append(caption);}
   gallery.append(figure);
  }
  if(gallery.childElementCount)target.append(gallery);
 }
};
