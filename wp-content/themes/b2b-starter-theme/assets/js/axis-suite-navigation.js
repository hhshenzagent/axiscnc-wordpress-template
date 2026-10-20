document.body.classList.add('is-enhanced');
const header=document.querySelector('.site-header');
const nav=document.querySelector('#primary-nav');
const mobileToggle=document.querySelector('.mobile-toggle');
const scrim=document.querySelector('.scrim');
const desktop=matchMedia('(min-width:1101px)');
const items=[...document.querySelectorAll('.nav-item')];
// Keep the menu overlay aligned with the actual compact header, including translations.
const syncHeaderHeight=()=>document.documentElement.style.setProperty('--axis-header-height',header.getBoundingClientRect().height+'px');
new ResizeObserver(syncHeaderHeight).observe(header);syncHeaderHeight();
let openItem=null;
function syncScrim(){scrim.hidden=!openItem&&!nav.classList.contains('mobile-open')}
function closeItem(focus=false){if(!openItem)return;const btn=openItem.querySelector('.disclosure');openItem.classList.remove('open');btn.setAttribute('aria-expanded','false');openItem=null;if(focus)btn.focus();syncScrim()}
function open(item){if(!item.querySelector('.mega'))return;closeItem();openItem=item;item.classList.add('open');item.querySelector('.disclosure').setAttribute('aria-expanded','true');syncScrim()}
function closeMobile(focus=false){closeItem();nav.classList.remove('mobile-open');mobileToggle.setAttribute('aria-expanded','false');syncScrim();if(focus)mobileToggle.focus()}
function selectGroup(item,index,focus=false){const btns=[...item.querySelectorAll('.group-select')];const panels=[...item.querySelectorAll('.group-panel')];btns.forEach((btn,i)=>btn.setAttribute('aria-pressed',String(i===index)));panels.forEach((panel,i)=>panel.hidden=i!==index);if(focus)btns[index].focus()}
items.forEach(item=>{const btn=item.querySelector('.disclosure');if(!btn)return;selectGroup(item,0);btn.addEventListener('click',()=>{if(openItem===item)closeItem();else open(item)});item.addEventListener('pointerenter',e=>{if(desktop.matches&&e.pointerType==='mouse'&&!item.contains(document.activeElement))open(item)});item.addEventListener('pointerleave',()=>{if(desktop.matches&&openItem===item&&!item.contains(document.activeElement))closeItem()});item.addEventListener('keydown',e=>{if(e.key==='Escape'&&openItem===item){e.preventDefault();e.stopPropagation();closeItem(true)}else if(e.key==='ArrowDown'&&e.target===btn){e.preventDefault();open(item);item.querySelector('.group-select,.ar-material-menu a')?.focus()}});const btns=[...item.querySelectorAll('.group-select')];btns.forEach((b,i)=>{b.addEventListener('click',()=>selectGroup(item,i));b.addEventListener('keydown',e=>{let target;if(e.key==='ArrowDown'||e.key==='ArrowRight')target=(i+1)%btns.length;else if(e.key==='ArrowUp'||e.key==='ArrowLeft')target=(i-1+btns.length)%btns.length;else if(e.key==='Home')target=0;else if(e.key==='End')target=btns.length-1;else return;e.preventDefault();selectGroup(item,target,true)})})});
mobileToggle.addEventListener('click',()=>{if(nav.classList.contains('mobile-open'))closeMobile();else{nav.classList.add('mobile-open');mobileToggle.setAttribute('aria-expanded','true');syncScrim()}});
document.addEventListener('click',e=>{if(!header.contains(e.target))closeMobile();else if(e.target.closest('nav a'))closeMobile()});
header.addEventListener('focusout',e=>{if(e.relatedTarget&&!header.contains(e.relatedTarget))closeMobile()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&nav.classList.contains('mobile-open')){e.preventDefault();closeMobile(true)}});
desktop.addEventListener('change',()=>closeMobile());
