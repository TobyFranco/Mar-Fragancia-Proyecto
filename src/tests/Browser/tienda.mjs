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
   console.log('Contraste panel:', await contrast('#panel-chanel .climate-strip'));
   assert.ok(await contrast('#panel-chanel .climate-strip') >= 4.5, 'Contraste de texto del panel >= 4.5');
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
if(process.argv[2]==='task5'){
 for(const [path,name] of [['/marcas','marcas'],['/marcas/chanel','marca-chanel'],['/perfumes?genero=mujer','catalogo'],['/perfumes?marca=chanel&genero=mujer','catalogo-filtrado'],['/buscar?q=sau','busqueda'],['/buscar?q=savaje','busqueda-vacia']]){
  for(const width of [1440,375]){await navigate(baseUrl + path,width);const m=await metrics();assert(m.scroll<=width,JSON.stringify({name,...m}));
   if(name === 'catalogo') {
    assert.equal(await evaluate(`getComputedStyle(document.querySelector('.catalog-grid')).gridTemplateColumns.split(' ').length`), width === 1440 ? 3 : 2, 'Columnas del catálogo según maqueta y viewport');
    assert.equal(await evaluate(`document.querySelector('.catalog-filters').open`),width === 1440);
    assert.equal(await evaluate(`new FormData(document.querySelector('#filtros-catalogo')).get('genero')`),'mujer');
   }
   if(name === 'catalogo-filtrado') assert.ok(await contrast('.active-filters > span') >= 4.5, 'Contraste de filtros activos >= 4.5');
   await capture(`${name}-${width}`);console.log(name,m)}
 }
 for(const [section,name] of [['s2','catalogo'],['s3','marcas'],['t1','busqueda']]){
  for(const width of [1440,375]){await navigate(maqueta,width);await evaluate(`(() => { const root=document.querySelector('#${section} .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style) })()`);await capture(`maqueta-${name}-${width}`)}
 }
}
if (process.argv[2] === 'task6') {
 for (const width of [1440,375]) {
  await navigate(baseUrl + '/perfumes/sauvage', width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('input[data-tamano="10 ml"]').disabled`), true);
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.perfume-bottom-bar')).display !== 'none'`), width === 375);
  await capture(`perfume-${width}`);
  await navigate(baseUrl + '/perfumes/bleu-de-chanel', width);
  await evaluate(`document.querySelector('input[data-tamano="10 ml"]').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('.selected-price').textContent.trim()`), '₲ 68.000');
  await evaluate(`const input = document.querySelector('#cantidad-perfume'); input.value = 999; input.dispatchEvent(new Event('input', {bubbles:true})); input.dispatchEvent(new Event('change', {bubbles:true}))`); await settle();
  assert.equal(await evaluate(`Number(document.querySelector('#cantidad-perfume').value)`), 12);
  console.log('Detalle: selección, cantidad y viewport', width);
 }
 for (const [section,name] of [['s4','perfume'],['s8','perfume-movil']]) {
  for (const width of [1440,375]) {
   await navigate(maqueta,width);
   await evaluate(`(() => {const root=document.querySelector('#${section} .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
   await capture(`maqueta-${name}-${width}`);
  }
 }
}
if (process.argv[2] === 'task7') {
 for (const width of [1440,375]) {
  await limpiarCarrito(width);
  await navigate(baseUrl + '/perfumes/sauvage',width);
  await evaluate(`document.querySelector('#agregar-perfume').requestSubmit()`);
  await until(`document.querySelector('.cart-dialog')?.open === true`);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('.cart-dialog').contains(document.activeElement)`),true);
  assert.equal(await evaluate(`Math.round(document.querySelector('.cart-dialog').getBoundingClientRect().width)`), Math.min(380,width), 'Ancho del panel de la maqueta');
  await capture(`carrito-panel-${width}`);
  const antes = Number(await evaluate(`document.querySelector('.cart-dialog input[name=cantidad]').value`));
  await evaluate(`document.querySelector('.cart-dialog button[aria-label^="Sumar una unidad"]').click()`);
  await until(`document.querySelector('.cart-dialog')?.open === true && Number(document.querySelector('.cart-dialog input[name=cantidad]').getAttribute('value')) === ${antes+1}`);
  await evaluate(`(() => {const items=[...document.querySelector('.cart-dialog').querySelectorAll('a[href],button:not(:disabled),input:not([type="hidden"])')].filter(el=>el.getClientRects().length);items.at(-1).focus()})()`);
  await key('\uE004');
  assert.equal(await evaluate(`document.querySelector('.cart-dialog').contains(document.activeElement)`),true,'Tab queda dentro del diálogo');
  await key('\uE00C'); await settle();
  assert.equal(await evaluate(`document.querySelector('.cart-dialog').open`),false);
  assert.equal(await evaluate(`document.activeElement === document.querySelector('.cart-link')`),true);
  await navigate(baseUrl + '/carrito',width); await capture(`carrito-${width}`);
  assert.ok((await metrics()).scroll <= width);
  await evaluate(`document.querySelector('.cart-line-price form').requestSubmit()`);
  await until(`document.querySelector('.search-empty')?.textContent.includes('Tu carrito está vacío')`);
  await capture(`carrito-vacio-${width}`);
  console.log('Carrito: agregar, panel, foco, Escape, quitar y vacío',width);
 }
 for (const width of [1440,375]) {
  await navigate(maqueta,width);
  await evaluate(`(() => {const root=document.querySelector('#s5 .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
  await capture(`maqueta-carrito-${width}`);
 }
}
if (process.argv[2] === 'task8') {
 for (const width of [1440,375]) {
  await limpiarCarrito(width);
  await navigate(baseUrl + '/perfumes/sauvage',width);
  await evaluate(`document.querySelector('#agregar-perfume').requestSubmit()`);
  await until(`document.querySelector('.cart-dialog')?.open === true`);
  await navigate(baseUrl + '/checkout', width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('#checkout-total').textContent.trim()`), '₲ 55.000');
  await evaluate(`document.querySelector('input[value="retiro"]').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('#checkout-total').textContent.trim()`), '₲ 35.000');
  assert.equal(await evaluate(`document.querySelector('#direccion').disabled`),true);
  await evaluate(`document.querySelector('input[value="interior"]').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('#checkout-envio').textContent.trim()`), 'A coordinar');
  await evaluate(`document.querySelector('input[value="asuncion"]').click()`); await settle();
  await capture(`checkout-${width}`);
  assert.ok(await contrast('.button-whatsapp') >= 4.5, 'Contraste del botón WhatsApp >= 4.5');
  await evaluate(`(() => {for(const [id,value] of Object.entries({nombre_contacto:'María Paz & Luz',telefono:'0981 23',ciudad:'Lambaré',barrio:'Centro',direccion:'Av. Cacique Lambaré 1234',referencia:'Portón negro'})){const input=document.getElementById(id);input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}))}document.querySelector('.checkout-page').requestSubmit()})()`);
  await until(`document.querySelector('#error-telefono')?.textContent.includes('Revisá el número')`);
  await capture(`checkout-error-${width}`);
  assert.equal(await evaluate(`document.querySelector('#nombre_contacto').value`),'María Paz & Luz');
  await evaluate(`document.querySelector('#telefono').value='+595 981 234 567'`);
  const resultado = await evaluate(`fetch(document.querySelector('.checkout-page').action,{method:'POST',body:new FormData(document.querySelector('.checkout-page')),redirect:'manual'}).then(r => r.type)`);
  assert.equal(resultado,'opaqueredirect','Guarda el pedido sin abrir conexión a WhatsApp en esta prueba');
  await navigate(baseUrl + '/carrito',width);
  const pedidoUrl = await evaluate(`Array.from(document.querySelectorAll('a[href]')).find(a=>a.textContent.startsWith('Volver a mi pedido')).href`);
  await navigate(pedidoUrl,width);
  assert.ok((await metrics()).scroll <= width);
  const whatsapp = await evaluate(`document.querySelector('.order-actions .button-whatsapp').href`);
  assert.ok(whatsapp.includes('%23MF-'));
  assert.equal(new URL(whatsapp).hash,'');
  assert.ok(decodeURIComponent(new URL(whatsapp).search.slice(6)).includes('María Paz & Luz'));
  await capture(`pedido-enviado-${width}`);
  console.log('Checkout: métodos, total, errores, recuperación y URL codificada',width);
 }
 for (const [section,name] of [['s6','checkout'],['t3','pedido-enviado']]) {
  for (const width of [1440,375]) {
   await navigate(maqueta,width);
   await evaluate(`(() => {const root=document.querySelector('#${section} .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
   await capture(`maqueta-${name}-${width}`);
  }
 }
}
if (process.argv[2] === 'task9') {
 for (const width of [1440,375]) {
  await navigate(baseUrl + '/termometro-olfativo',width);
  assert.ok((await metrics()).scroll <= width);
  for (const [temperatura,zona] of [[18,'frio'],[19,'templado'],[26,'templado'],[27,'calor'],[32,'calor'],[33,'calor_intenso']]) {
   await evaluate(`(() => {const el=document.querySelector('#temperatura');el.value=${temperatura};el.dispatchEvent(new Event('input',{bubbles:true}))})()`); await settle();
   assert.equal(await evaluate(`document.querySelector('.thermometer-page').dataset.zona`),zona);
  }
  await evaluate(`document.querySelector('#momento-dia').click();document.querySelector('#humedad-seco').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('#dosis-texto').textContent.trim()`),'1 aplicación');
  await evaluate(`document.querySelector('#momento-noche').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('#dosis-texto').textContent.trim()`),'2 aplicaciones');
  await evaluate(`document.querySelector('#humedad-humedo').click()`); await settle();
  assert.equal(await evaluate(`document.querySelector('#dosis-texto').textContent.trim()`),'1 aplicación');
  await evaluate(`document.querySelector('[data-mes="Jun"]').click()`); await settle();
  assert.equal(await evaluate(`Number(document.querySelector('#temperatura').value)`),22);
  assert.equal(await evaluate(`document.querySelector('.thermometer-page').dataset.zona`),'templado');
  assert.equal(await evaluate(`document.querySelectorAll('.climate-months button').length`),12);
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('[data-mes=Ene]')).backgroundColor === getComputedStyle(document.querySelector('[data-mes=Jun]')).backgroundColor`),false,'Meses distinguen zonas por color');
  await capture(`termometro-${width}`);
  await evaluate(`document.querySelector('.weather-now button').click()`); await settle();
  assert.ok((await metrics()).scroll <= width);
  console.log('Termómetro: bordes, dosis, meses y clima',width);
 }
 for (const width of [1440,375]) {
  await navigate(maqueta,width);
  await evaluate(`(() => {const root=document.querySelector('#n2 .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
  await capture(`maqueta-termometro-${width}`);
 }
}
if (process.argv[2] === 'task10') {
 for (const width of [1440,375]) {
  await navigate(baseUrl + '/login',width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('.auth-tabs [aria-current=page]').textContent.trim()`),'Ingresar');
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.auth-page')).gridTemplateColumns.split(' ').length`),width === 1440 ? 2 : 1);
  await capture(`login-${width}`);
  await evaluate(`document.querySelector('#auth-email').value='mariapaz@example.com';document.querySelector('#auth-password').value='incorrecta';document.querySelector('.auth-form').requestSubmit()`);
  await until(`document.querySelector('#auth-error-email')?.textContent.includes('Credenciales incorrectas')`);
  assert.equal(await evaluate(`document.querySelector('#auth-email').value`),'mariapaz@example.com');
  assert.equal(await evaluate(`document.querySelector('#auth-password').value`),'');
  await capture(`login-error-${width}`);
  await navigate(baseUrl + '/register',width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('.auth-tabs [aria-current=page]').textContent.trim()`),'Crear cuenta');
  await capture(`registro-${width}`);
  await evaluate(`(() => {for(const [id,value] of Object.entries({'auth-nombre':'María Paz','auth-email':'visual@example.com','auth-password':'password123','auth-password_confirmation':'distinta'}))document.getElementById(id).value=value;document.querySelector('.auth-form').requestSubmit()})()`);
  await until(`document.querySelector('#auth-error-password')?.textContent.includes('Las contraseñas no coinciden')`);
  assert.equal(await evaluate(`document.querySelector('#auth-nombre').value`),'María Paz');
  assert.equal(await evaluate(`document.querySelector('#auth-email').value`),'visual@example.com');
  assert.equal(await evaluate(`document.querySelector('#auth-password').value`),'');
  await capture(`registro-error-${width}`);
  console.log('Auth: vistas, navegación, errores y datos conservados',width);
 }
 for (const width of [1440,375]) {
  await navigate(maqueta,width);
  await evaluate(`(() => {const root=document.querySelector('#s7 .mf');for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
  await capture(`maqueta-auth-${width}`);
 }
}
if (process.argv[2] === 'task11b') {
 for (const width of [1440,375]) {
  await navigate(baseUrl+'/como-funciona',width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelectorAll('.how-faq details').length`),4);
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.how-steps section')).backgroundColor`),'rgb(244, 246, 243)','Tarjetas con fondo sage de la maqueta');
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.how-steps section')).borderRadius`),'12px');
  if (width === 1440) {
   assert.equal(await evaluate(`document.querySelector('.info-links [aria-current="page"]')?.textContent`),'Cómo funciona','Se identifica la página actual');
   assert.equal(await evaluate(`getComputedStyle(document.querySelector('.info-links [aria-current="page"]')).textDecorationLine`),'underline','Página informativa activa subrayada');
  }
  await capture(`como-funciona-${width}`);
  await evaluate(`document.querySelector('.how-faq summary').focus()`);
  await key('\uE007'); await settle();
  assert.equal(await evaluate(`document.querySelector('.how-faq details').open`),true,'FAQ funciona por teclado');
  assert.ok(await evaluate(`document.querySelector('.how-faq details p').textContent.includes('perfume original')`));
  await capture(`como-funciona-faq-${width}`);
  await navigate(baseUrl+'/pagina-inexistente',width);
  assert.ok((await metrics()).scroll <= width);
  assert.equal(await evaluate(`document.querySelector('h1').textContent.trim()`),'No encontramos esta página');
  assert.equal(await evaluate(`document.querySelector('.not-found-page a').getAttribute('href')`),baseUrl);
  await capture(`no-encontrada-${width}`);
  console.log('Cómo funciona/404: FAQ con teclado, salidas y viewport',width);
 }
 for (const [section,index,name] of [['t2',0,'como-funciona'],['t4',0,'estados-vacios']]) {
  for (const width of [1440,375]) {
   await navigate(maqueta,width);
   await evaluate(`(() => {const root=document.querySelectorAll('#${section} .mf')[${index}];for(const style of [...document.querySelectorAll('style,link[rel="stylesheet"]')])document.head.append(style);document.body.replaceChildren(root);const style=document.createElement('style');style.textContent='body{margin:0!important;padding:0!important}html{overflow-x:clip}';document.head.append(style)})()`);
   await capture(`maqueta-${name}-${width}`);
  }
 }
}
if (process.argv[2] === 'task11a-estilos') {
 const errores=[];
 const check=(mensaje,actual,esperado)=>{try{assert.deepEqual(actual,esperado,mensaje)}catch(e){errores.push(e.message)}};
 for(const width of [1440,375]) {
  await navigate(baseUrl+'/perfumes/sauvage',width);
  check('Un solo Agregar visible '+width,await evaluate(`Array.from(document.querySelectorAll('button[form="agregar-perfume"],.add-perfume')).filter(el=>el.getClientRects().length>0).length`),1);
 }
 await navigate(baseUrl+'/marcas/chanel',1440);
 check('Marca activa subrayada',await evaluate(`getComputedStyle(document.querySelector('[aria-controls="panel-chanel"]')).textDecorationLine`),'underline');
 await navigate(baseUrl+'/termometro-olfativo',1440);
 check('Termómetro activo subrayado',await evaluate(`getComputedStyle(document.querySelector('.info-links [aria-current="page"]')).textDecorationLine`),'underline');
 for(const [t,color] of [[18,'rgb(238, 243, 251)'],[22,'rgb(242, 246, 241)'],[30,'rgb(255, 247, 232)'],[36,'rgb(255, 241, 232)']]) {
  await evaluate(`(() => {const e=document.querySelector('#temperatura');e.value=${t};e.dispatchEvent(new Event('input',{bubbles:true}))})()`); await settle();
  check('Fondo '+t,await evaluate(`getComputedStyle(document.querySelector('.thermometer-page')).backgroundColor`),color);
 }
 check('Transición suave',await evaluate(`getComputedStyle(document.querySelector('.thermometer-page')).transitionDuration`),process.env.TIENDA_REDUCED_MOTION ? '0s' : '0.2s');
 if(process.env.TIENDA_REDUCED_MOTION) check('Preferencia reducida activa',await evaluate(`matchMedia('(prefers-reduced-motion: reduce)').matches`),true);
 if(errores.length) throw new Error(errores.join('\n'));
 if (!process.env.TIENDA_REDUCED_MOTION) {
  for (const width of [1440,375]) {
   await navigate(baseUrl+'/termometro-olfativo',width);
   for (const [temperatura,zona] of [[18,'frio'],[22,'templado'],[30,'calor'],[36,'calor-intenso']]) {
    await evaluate(`(() => {const e=document.querySelector('#temperatura');e.value=${temperatura};e.dispatchEvent(new Event('input',{bubbles:true}))})()`); await settle();
    assert.ok((await metrics()).scroll <= width);
    await capture(`termometro-${zona}-${width}`);
   }
  }
 }
 console.log('Retoques CSS: desktop/móvil, marca y cuatro fondos correctos');
}
if (process.argv[2] === 'task11a-busqueda') {
 for(const width of [1440,375]) {
  await navigate(baseUrl+'/buscar',width);
  await evaluate(`const input=document.querySelector('#buscar-perfume');input.value='hermes';input.dispatchEvent(new Event('input',{bubbles:true}))`);
  await until(`document.querySelector('#sugerencias-busqueda mark')?.textContent === 'Hermès'`);
  assert.equal(new URL(await evaluate('location.href')).search,'','Escribir no navega');
  assert.ok((await metrics()).scroll <= width);
  await capture(`busqueda-viva-${width}`);
  await evaluate(`(() => {const input=document.querySelector('#buscar-perfume');input.value='vainilla';input.dispatchEvent(new Event('input',{bubbles:true}))})()`);
  await until(`document.querySelector('#sugerencias-busqueda').textContent.includes('Black Opium') && document.querySelector('#sugerencias-busqueda mark')?.textContent === 'vainilla'`);
  await evaluate(`(() => {const input=document.querySelector('#buscar-perfume');input.value='sau';input.dispatchEvent(new Event('input',{bubbles:true}))})()`);
  await until(`document.querySelector('#sugerencias-busqueda mark')?.textContent === 'Sau'`);
  await evaluate(`document.querySelector('.search-form').requestSubmit()`);
  await until(`location.search === '?q=sau' && document.querySelector('.search-page') !== null`);
  assert.ok(await evaluate(`document.querySelector('#resultados-perfumes').textContent.includes('Sauvage')`));
  console.log('Búsqueda incremental por marca/nota/perfume, resaltado y Enter',width);
 }
}
assert.deepEqual(browserErrors, [], 'Sin errores de consola durante el recorrido');
} finally {
 await call('session.end');
 ws.close();
}

