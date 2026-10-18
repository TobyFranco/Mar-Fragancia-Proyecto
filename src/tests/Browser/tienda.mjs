import fs from 'node:fs';
import assert from 'node:assert/strict';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
const root = fileURLToPath(new URL('../../../', import.meta.url));
const dir = process.env.TIENDA_CAPTURAS || path.join(root, '.superpowers/sdd/2026-10-18-fase-1-tienda-publica');
fs.mkdirSync(dir, { recursive: true });
const baseUrl = process.env.TIENDA_URL || 'http://127.0.0.1:8013';
const maqueta = pathToFileURL(path.join(root, 'docs/diseno/maquetas/diseno-final.html')).href;
const ws = new WebSocket(process.env.FIREFOX_BIDI_URL || 'ws://127.0.0.1:9223/session');
await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true})});
let id=0;
const pending=new Map();
ws.addEventListener('message',event=>{const msg=JSON.parse(event.data);if(!msg.id)return;const p=pending.get(msg.id);if(p){pending.delete(msg.id);msg.type==='error'?p.reject(new Error(JSON.stringify(msg))):p.resolve(msg.result)}});
function call(method,params={}){return new Promise((resolve,reject)=>{const n=++id;pending.set(n,{resolve,reject});ws.send(JSON.stringify({id:n,method,params}))})}
await call('session.new',{capabilities:{}});
const {context}=await call('browsingContext.create',{type:'tab'});
async function evaluate(expression){const r=await call('script.evaluate',{expression,target:{context},awaitPromise:true});if(r.type==='exception')throw new Error(JSON.stringify(r));return r.result?.value}
async function settle(){await evaluate('document.fonts.ready.then(() => new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))).then(() => Promise.all(document.getAnimations().map(a => a.finished.catch(() => {}))))')}
async function navigate(url,width){await call('browsingContext.setViewport',{context,viewport:{width,height:1000},devicePixelRatio:1});await call('browsingContext.navigate',{context,url,wait:'complete'});await settle()}
async function capture(name){await settle();const r=await call('browsingContext.captureScreenshot',{context,origin:'document'});fs.writeFileSync(`${dir}/${name}.png`,Buffer.from(r.data,'base64'))}
async function metrics(){return JSON.parse(await evaluate(`JSON.stringify({width:innerWidth,scroll:document.documentElement.scrollWidth,font:getComputedStyle(document.body).fontFamily,h1:document.querySelector('h1')?.textContent,hero:document.querySelector('.hero')?.getBoundingClientRect().height})`))}
try {
if(process.argv[2]==='task4'){
 for(const width of [1440,375]){
  await navigate(baseUrl + '/',width);
  let m=await metrics();assert(m.scroll<=width,JSON.stringify(m));
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.account-link')).display !== 'none'`),true);
  assert.equal(await evaluate('!!window.Alpine'),true);
  assert.equal(await evaluate(`Array.from(document.querySelectorAll('a[aria-label="Buscar perfumes"]')).filter(el => el.getBoundingClientRect().width > 0).length`),1,'Solo una lupa visible por viewport');
  await capture(`inicio-${width}`);
  if(width===1440){
   await evaluate(`document.querySelector('[aria-controls="panel-chanel"]').focus()`);await settle();
   assert.equal(await evaluate(`getComputedStyle(document.querySelector('#panel-chanel')).display !== 'none'`),true);
   await capture(`panel-marca-${width}`);
   await evaluate(`document.querySelector('#panel-chanel .panel-close').focus(); document.querySelector('#panel-chanel .panel-close').click()`);await settle();
   assert.equal(await evaluate(`getComputedStyle(document.querySelector('#panel-chanel')).display`),'none','El botón cerrar debe cerrar el panel y devolver el foco');
   await evaluate(`document.querySelector('[aria-controls="panel-chanel"]').blur(); document.querySelector('[aria-controls="panel-chanel"]').focus()`);await settle();
   await evaluate(`document.activeElement.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}))`);await settle();
   assert.equal(await evaluate(`getComputedStyle(document.querySelector('#panel-chanel')).display`),'none');
  }else{
   await evaluate(`document.querySelector('.menu-button').click()`);await settle();
   assert.equal(await evaluate(`document.querySelector('.brand-nav').classList.contains('is-open')`),true);
   await capture(`menu-marcas-${width}`);
  }
  console.log('Inicio',m);
 }
 for(const [section,name] of [['s1','inicio'],['n1','panel-marca']]){
  for(const width of [1440,375]){
   await navigate(maqueta,width);
   await evaluate(`(() => { const root=document.querySelector('#${section} .mf'); for(const style of [...document.querySelectorAll('style, link[rel="stylesheet"]')]) document.head.append(style); document.body.replaceChildren(root); const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important;background:white!important}html{overflow-x:clip}';document.head.append(style) })()`);
   await capture(`maqueta-${name}-${width}`);
  }
 }
}
} finally {
 await call('session.end');
 ws.close();
}

