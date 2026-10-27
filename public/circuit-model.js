/* Ideal 24 V DC relay model; independent of presentation. */
(function(root){
'use strict';
function initial(fault='none'){return {power:true,start:false,stop:false,k:false,fault};}
function step(s,patch={}){const n={...s,...patch};const supply=n.power&&n.fault!=='fuse';const hold=n.k&&n.fault!=='hold';n.k=!!(supply&&!n.stop&&(n.start||hold));return n;}
function readings(s){const top=s.power?24:0;const supply=s.power&&s.fault!=='fuse';return {supply:top,afterFuse:supply?24:0,afterStop:supply&&!s.stop?24:0,coil:s.k?24:0,lamp:s.k};}
const api={initial,step,readings};if(typeof module!=='undefined')module.exports=api;else root.MechaCircuitModel=api;
})(globalThis);
