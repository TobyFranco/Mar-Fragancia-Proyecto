import fs from 'node:fs';
import assert from 'node:assert/strict';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
const root = fileURLToPath(new URL('../../../', import.meta.url));
const dir = process.env.TIENDA_CAPTURAS || path.join(root, '.superpowers/sdd/2026-10-19-fase-2-panel-admin');
fs.mkdirSync(dir, { recursive: true });
const baseUrl = process.env.TIENDA_URL || 'http://127.0.0.1:8014';
const maqueta = pathToFileURL(path.join(root, 'docs/diseno/maquetas/diseno-final.html')).href;
const ws = new WebSocket(process.env.FIREFOX_BIDI_URL || 'ws://127.0.0.1:9225/session');
await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true})});
let id=0;
const pending=new Map();
const browserErrors=[];
ws.addEventListener('message',event=>{const msg=JSON.parse(event.data);if(msg.method === 'log.entryAdded' && msg.params.level === 'error') browserErrors.push(msg.params.text);if(!msg.id)return;const p=pending.get(msg.id);if(p){pending.delete(msg.id);msg.type==='error'?p.reject(new Error(JSON.stringify(msg))):p.resolve(msg.result)}});
function call(method,params={}){return new Promise((resolve,reject)=>{const n=++id;pending.set(n,{resolve,reject});ws.send(JSON.stringify({id:n,method,params}))})}
await call('session.new',{capabilities:{}});
const {context}=await call('browsingContext.create',{type:'tab'});
await call('session.subscribe',{events:['log.entryAdded'],contexts:[context]});
async function evaluate(expression){const r=await call('script.evaluate',{expression,target:{context},awaitPromise:true});if(r.type==='exception')throw new Error(JSON.stringify(r));return r.result?.value}
async function settle(){await evaluate('document.fonts.ready.then(() => new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))).then(() => Promise.all(document.getAnimations().map(a => a.finished.catch(() => {}))))')}
async function navigate(url,width){await call('browsingContext.setViewport',{context,viewport:{width,height:1000},devicePixelRatio:1});await call('browsingContext.navigate',{context,url,wait:'complete'});await settle()}
async function capture(name){await evaluate("window.scrollTo({top:0,left:0,behavior: 'instant'})");await settle();const r=await call('browsingContext.captureScreenshot',{context,origin:(name.startsWith('maqueta-') || name.startsWith('carrito-panel-')) ? 'viewport' : 'document'});fs.writeFileSync(`${dir}/${name}.png`,Buffer.from(r.data,'base64'))}
async function until(expression) {
 const deadline = Date.now() + 10000;
 while (Date.now() < deadline) {
  try { if (await evaluate(expression)) { await settle(); return } } catch {}
  await new Promise(resolve => setTimeout(resolve, 100));
 }
 console.error('Browser errors:',browserErrors);
 console.error(await evaluate(`JSON.stringify({url:location.href,contenido:document.querySelector('#sugerencias-busqueda')?.textContent.slice(0,500),estado:document.querySelector('.search-status')?.textContent,alpine:!!window.Alpine})`));
 throw new Error('No se cumplió: ' + expression);
}
async function limpiarCarrito(width) {
 await navigate(baseUrl + '/carrito', width);
 await evaluate(`(async () => {for(const form of [...document.querySelectorAll('form')].filter(f=>f.querySelector('input[name=_method][value=DELETE]'))) await fetch(form.action,{method:'POST',body:new FormData(form)})})()`);
}
async function key(value) { await call('input.performActions', {context, actions:[{type:'key',id:'keyboard',actions:[{type:'keyDown',value},{type:'keyUp',value}]}]}) }
async function metrics(){return JSON.parse(await evaluate(`JSON.stringify({width:innerWidth,scroll:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,h1:document.querySelector('h1')?.textContent,hero:document.querySelector('.hero')?.getBoundingClientRect().height})`))}
async function contrast(selector) {
 return evaluate(`(() => {
  const style = getComputedStyle(document.querySelector(${JSON.stringify(selector)}));
  const luminance = color => color.match(/[0-9.]+/g).slice(0,3).map(Number).map(v => color.startsWith('color(srgb') ? v : v/255).map(v => v <= .04045 ? v/12.92 : ((v+.055)/1.055)**2.4).reduce((sum,v,i) => sum + v * [.2126,.7152,.0722][i],0);
  const values = [luminance(style.color),luminance(style.backgroundColor)].sort((a,b) => a-b);
  return (values[1]+.05)/(values[0]+.05);
 })()`);
}
try {
 await navigate(baseUrl+'/login',1440);
 await evaluate(`document.querySelector('[name=email]').value='admin@marfragancia.com';document.querySelector('[name=password]').value='password123';document.querySelector('form[action$="/login"]').requestSubmit()`);
 await until(`location.pathname === '/admin/dashboard'`);
 const pages = process.argv[2] === 'task4' ? [['/admin/dashboard','dashboard','a1']] : process.argv[2] === 'task5' ? [['/admin/pedidos','pedidos','a4'],['/admin/pedidos/1','pedido','a5']] : [['/admin/productos','productos','a2'],['/admin/productos/1/edit','producto','a3'],['/admin/productos/create','producto-nuevo','a3']];
 for(const [url,name,section] of pages) {
  for(const width of [1440,768]) {
   await navigate(baseUrl+url,width);
   assert.ok(await evaluate(`document.querySelector('.admin-sidebar') !== null`),'Layout admin');
   assert.ok((await metrics()).scroll <= width,JSON.stringify(await metrics()));
   assert.ok(await evaluate(`getComputedStyle(document.querySelector('h1')).fontFamily.includes('Montserrat')`));
   assert.ok(await evaluate(`Array.from(document.querySelectorAll('a,button,input:not([type=hidden]):not([type=checkbox]),select')).filter(e=>e.getClientRects().length && e.closest('.admin-sidebar')).every(e=>e.getBoundingClientRect().height>=44)`),'Objetivos del menú44px');
   if(name === 'pedidos' && width === 768) assert.ok(await evaluate(`document.querySelector('.admin-table-wrap table').getBoundingClientRect().width >= 780`),'Tabla de pedidos legible con scroll local');
   if(name === 'producto-nuevo' && width === 768) assert.equal(await evaluate(`(() => {const r=document.createRange();r.selectNodeContents(document.querySelector('.admin-presentations-table tbody tr td:nth-child(2)'));return r.getClientRects().length})()`),1,'Decant no se corta en tablet');
   await capture(`admin-${name}-${width}`);
   console.log(name,width,await metrics());
   if(name !== 'producto-nuevo') {
    await navigate(maqueta,width);
    await evaluate(`(() => {const root=document.querySelector('#${section} .adm');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}';document.head.append(style)})()`);
    await capture(`maqueta-admin-${name}-${width}`);
   }
  }
 }
 assert.deepEqual(browserErrors,[],'Sin errores de consola');
} finally { await call('session.end'); ws.close(); }
