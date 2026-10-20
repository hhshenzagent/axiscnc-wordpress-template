(() => {
 const choices=[...document.querySelectorAll('.capability-choice')];
 const panels=[...document.querySelectorAll('.cap-detail')];
 const narrow=matchMedia('(max-width:900px)');
 function placePanels(){panels.forEach((p,i)=>(narrow.matches?document.querySelector(`[data-slot="${i}"]`):document.querySelector('.desktop-cap-slot')).append(p))}
 function selectCapability(i,focus=false){choices.forEach((b,k)=>{b.setAttribute('aria-expanded',String(k===i));b.querySelector('.choice-plus').textContent=k===i?'−':'+';panels[k].hidden=k!==i});if(focus)choices[i].focus()}
 choices.forEach((b,i)=>{b.addEventListener('click',()=>selectCapability(i));b.addEventListener('keydown',event=>{let n;if(['ArrowDown','ArrowRight'].includes(event.key))n=(i+1)%choices.length;else if(['ArrowUp','ArrowLeft'].includes(event.key))n=(i-1+choices.length)%choices.length;else if(event.key==='Home')n=0;else if(event.key==='End')n=choices.length-1;else return;event.preventDefault();selectCapability(n,true)})});
 placePanels();narrow.addEventListener('change',placePanels);

})();
