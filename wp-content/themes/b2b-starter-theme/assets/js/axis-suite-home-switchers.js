(() => {
 document.querySelectorAll('[data-switcher]').forEach(module=>{
  const tabs=[...module.querySelectorAll('[role="tab"]')];
  const panels=[...module.querySelectorAll('[role="tabpanel"]')];
  const vertical=module.querySelector('[role="tablist"]').getAttribute('aria-orientation')==='vertical';
  function activate(index,focus=false){tabs.forEach((t,i)=>{t.setAttribute('aria-selected',String(i===index));t.tabIndex=i===index?0:-1;panels[i].hidden=i!==index});if(focus)tabs[index].focus()}
  tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>activate(index));tab.addEventListener('keydown',event=>{let next;const forward=vertical?'ArrowDown':'ArrowRight';const back=vertical?'ArrowUp':'ArrowLeft';if(event.key===forward)next=(index+1)%tabs.length;else if(event.key===back)next=(index-1+tabs.length)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();activate(next,true)})});
 });
 const solutionField=document.querySelector('#brief-form [name=solution]');
 const requestedSolution=new URL(location.href).searchParams.get('solution');
 if(solutionField&&[...solutionField.options].some(option=>option.value===requestedSolution))solutionField.value=requestedSolution;
 document.querySelectorAll('[data-brief-material]').forEach(link=>link.addEventListener('click',()=>{const field=document.querySelector('#brief-form [name="material"]');if(field)field.value=link.dataset.briefMaterial}));
 // Compact contact controls keep the page text and portraits visible.
 const inquiry=document.querySelector('.float-inquiry');
 if(inquiry){inquiry.setAttribute('aria-label','Quick inquiry');inquiry.title='Quick inquiry';inquiry.innerHTML='<span class="visually-hidden">Quick inquiry</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4H5v16h14v-9M14 4l6 1-8 8-4 1 1-4 8-8"/></svg>'}
 const chat=document.querySelector('.chat-toggle');
 if(chat){chat.setAttribute('aria-label','Chat with us');chat.title='Chat with us';chat.innerHTML='<span class="visually-hidden">Chat with us</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 0 1-8 8H8l-5 3 1-6a8 8 0 1 1 16-5Z"/><path d="M8 11h8M8 7h5"/></svg>'}
})();
