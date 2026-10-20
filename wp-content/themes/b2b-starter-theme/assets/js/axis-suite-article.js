(() => {
 const page=document.querySelector('.axis-reading-page');if(!page)return;
 const header=document.querySelector('.site-header'),toc=page.querySelector('.axis-reading-toc');
 const toggle=toc?.querySelector('button'),list=toc?.querySelector('ul');
 if(toggle&&list&&matchMedia('(max-width: 700px)').matches){toggle.setAttribute('aria-expanded','false');list.hidden=true;}
 toggle?.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!open));list.hidden=open;});
 const links=[...(toc?.querySelectorAll('li a')||[])],headings=links.map(a=>document.getElementById(decodeURIComponent(a.hash.slice(1)))).filter(Boolean);
 let queued=false;
 const sync=()=>{
  queued=false;
  const headerBottom=Math.max(0,header?.getBoundingClientRect().bottom||0),offset=headerBottom+20;
  page.style.setProperty('--article-sticky-top',offset+'px');
  let current=headings[0];for(const h of headings){if(h.getBoundingClientRect().top<=offset+45)current=h;else break;}
  links.forEach(a=>{const active=!!current&&a.hash==='#'+current.id;if(active)a.setAttribute('aria-current','location');else a.removeAttribute('aria-current');});
 };
 const schedule=()=>{if(!queued){queued=true;requestAnimationFrame(sync);}};
 addEventListener('scroll',schedule,{passive:true});addEventListener('resize',schedule);if(header&&'ResizeObserver'in window)new ResizeObserver(schedule).observe(header);sync();
 links.forEach(a=>a.addEventListener('click',event=>{
  const target=document.getElementById(decodeURIComponent(a.hash.slice(1)));if(!target)return;
  event.preventDefault();sync();
  const offset=parseFloat(page.style.getPropertyValue('--article-sticky-top'))||140;
  scrollTo({top:Math.max(0,scrollY+target.getBoundingClientRect().top-offset),behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
  history.replaceState(history.state,'',a.hash);target.setAttribute('tabindex','-1');target.focus({preventScroll:true});
 }));
 page.querySelectorAll('[data-article-print]').forEach(button=>button.addEventListener('click',()=>print()));
 page.querySelectorAll('[data-article-copy]').forEach(button=>button.addEventListener('click',async()=>{const status=button.closest('.axis-reading-share').querySelector('[data-article-status]');try{await navigator.clipboard.writeText(location.origin+location.pathname);status.textContent='Article link copied.';}catch{status.textContent='Copy the article URL from your address bar.';}}));
 const dialog=page.querySelector('#axis-reading-inquiry'),open=page.querySelector('[data-reading-inquiry]');
 open?.addEventListener('click',()=>{if(dialog){dialog.showModal();page.dataset.inquiryOpen='true';}});
 dialog?.querySelector('[data-reading-close]')?.addEventListener('click',()=>dialog.close());
 dialog?.addEventListener('close',()=>{delete page.dataset.inquiryOpen;open?.focus({preventScroll:true});});
 dialog?.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();});
})();
