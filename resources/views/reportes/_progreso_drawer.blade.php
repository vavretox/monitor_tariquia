<div id="progress-drawer" class="hidden" aria-hidden="true">
 <button type="button" class="progress-backdrop" data-progress-close aria-label="Cerrar progreso"></button>
 <aside class="progress-panel" role="dialog" aria-modal="true" aria-labelledby="progress-title" tabindex="-1">
 <header class="flex items-start justify-between gap-3 border-b bg-white p-5"><div><p class="text-xs font-bold uppercase text-fuchsia-700">Progreso de la acción</p><h2 id="progress-title" class="text-xl font-bold"></h2><p id="progress-meta" class="mt-1 text-sm text-slate-500"></p></div><button type="button" data-progress-close class="ui-btn-secondary" aria-label="Cerrar">×</button></header>
 <div id="progress-events" class="progress-events"></div>
 <footer class="border-t bg-white p-4"><a id="progress-link" class="ui-btn-primary w-full justify-center ui-icon-chart" href="#" hidden>Abrir seguimiento completo</a></footer>
 </aside>
</div>
@push('styles')
<style>
#progress-drawer{position:fixed;inset:0;z-index:2200}#progress-drawer.hidden{display:none}.progress-backdrop{position:absolute;inset:0;width:100%;background:#03171399;backdrop-filter:blur(3px)}.progress-panel{position:absolute;right:0;top:0;height:100dvh;width:min(760px,96vw);display:flex;flex-direction:column;background:#f1f5f9;box-shadow:-15px 0 50px #0003}.progress-events{flex:1;min-height:0;overflow:auto;padding:2rem}.progress-timeline{position:relative}.progress-timeline:before{content:"";position:absolute;left:50%;top:0;bottom:0;width:3px;background:#db2777}.progress-event{position:relative;width:calc(50% - 26px);margin-bottom:24px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;padding:18px;box-shadow:0 2px 5px #0001}.progress-event:nth-child(even){margin-left:calc(50% + 26px)}.progress-event:after{content:"✓";position:absolute;top:26px;right:-42px;height:30px;width:30px;border:5px solid #fff;border-radius:50%;background:#10b981;color:white;text-align:center;font-size:13px;line-height:20px}.progress-event:nth-child(even):after{right:auto;left:-40px}.progress-event.observacion:after{background:#f59e0b}@media(max-width:640px){.progress-events{padding:1.2rem}.progress-timeline:before{left:14px}.progress-event,.progress-event:nth-child(even){width:calc(100% - 44px);margin-left:44px}.progress-event:after,.progress-event:nth-child(even):after{left:-44px;right:auto}}@media print{#progress-drawer,.js-report-progress{display:none!important}}

.progress-panel{animation:progressSlide .32s cubic-bezier(.2,.8,.2,1) both}
.progress-backdrop{animation:progressFade .25s ease both}
.progress-event{animation:progressCard .4s ease both;animation-delay:var(--event-delay,0ms);transition:box-shadow .2s,border-color .2s;overflow-wrap:anywhere}
.progress-event:hover{box-shadow:0 8px 22px #0f172a0d;border-color:#b9ddcf}
.progress-timeline:before{background:linear-gradient(#10b981,#db2777);border-radius:999px}
.progress-panel footer{box-shadow:0 -4px 18px #0f172a05}
@keyframes progressSlide{from{opacity:.5;transform:translateX(45px)}to{opacity:1;transform:translateX(0)}}
@keyframes progressFade{from{opacity:0}to{opacity:1}}
@keyframes progressCard{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@media(prefers-reduced-motion:reduce){#progress-drawer *{animation:none!important;transition:none!important}}
</style>
@endpush
@push('scripts')
<script>
(()=>{
 const drawer=document.getElementById('progress-drawer');if(!drawer)return;document.body.append(drawer);
 const panel=drawer.querySelector('aside'),title=document.getElementById('progress-title'),meta=document.getElementById('progress-meta'),events=document.getElementById('progress-events'),link=document.getElementById('progress-link');
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 let trigger,request,previousOverflow='';
 const close=()=>{request?.abort();drawer.classList.add('hidden');drawer.setAttribute('aria-hidden','true');document.body.style.overflow=previousOverflow;trigger?.focus()};
 drawer.querySelectorAll('[data-progress-close]').forEach(b=>b.addEventListener('click',close));
 document.addEventListener('click',async event=>{
  const button=event.target.closest('.js-report-progress');if(!button)return;
  trigger=button;request?.abort();request=new AbortController();const current=request;
  if(drawer.classList.contains('hidden'))previousOverflow=document.body.style.overflow;
  drawer.classList.remove('hidden');drawer.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';panel.focus();
  title.textContent='Progreso';meta.textContent='Cargando…';events.textContent='Cargando el proceso de la acción…';link.hidden=true;
  try{
   const response=await fetch(button.dataset.url,{headers:{Accept:'application/json'},signal:current.signal});if(!response.ok)throw new Error();
   const data=await response.json();if(current!==request)return;
   title.textContent=data.titulo;meta.textContent=String(data.estado).replaceAll('_',' ')+' · '+data.avance+'% · '+(data.responsables||'Sin responsables');
   link.href=data.url;link.hidden=false;
   events.innerHTML='<div class="progress-timeline">'+data.eventos.slice().reverse().map((e,index)=>'<article class="progress-event '+(e.tipo==='observacion'?'observacion':'')+'" style="--event-delay:'+Math.min(index,7)*55+'ms"><p class="text-xs font-bold uppercase text-emerald-700">'+esc(e.usuario)+'</p><h3 class="mt-2 font-bold">'+esc(e.titulo)+(e.avance!==null?' · '+esc(e.avance)+'%':'')+'</h3><p class="mt-2 whitespace-pre-line border-t pt-2 text-sm text-slate-600">'+esc(e.descripcion)+'</p><time class="mt-3 block text-xs text-slate-500">'+esc(e.fecha)+'</time></article>').join('')+'</div>';
  }catch(error){if(error.name==='AbortError')return;meta.textContent='';events.textContent='No se pudo cargar el progreso. Intenta nuevamente.'}
 });
 drawer.addEventListener('keydown',event=>{
  if(event.key==='Escape'){close();return}
  if(event.key==='Tab'){const items=[...panel.querySelectorAll('button,a[href]')].filter(el=>!el.hidden);const first=items[0],last=items.at(-1);if(event.shiftKey&&(document.activeElement===first||document.activeElement===panel)){event.preventDefault();last.focus()}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}}
 });
})();
</script>
@endpush
