(() => {
 document.querySelectorAll('[data-suite-tabs]').forEach(root=>{
  const tabs=[...root.querySelectorAll('[role=tab]')],panels=[...root.querySelectorAll('[role=tabpanel]')];
  function select(i,focus=false){tabs.forEach((t,k)=>{t.setAttribute('aria-selected',String(i===k));t.tabIndex=i===k?0:-1;panels[k].hidden=i!==k});if(focus)tabs[i].focus()}
  tabs.forEach((t,i)=>{t.addEventListener('click',()=>select(i));t.addEventListener('keydown',event=>{let n;if(event.key==='ArrowRight')n=(i+1)%tabs.length;else if(event.key==='ArrowLeft')n=(i-1+tabs.length)%tabs.length;else if(event.key==='Home')n=0;else if(event.key==='End')n=tabs.length-1;else return;event.preventDefault();select(n,true)})});
 });
 // Existing Fluent Forms owns validation, attachments, submission and attribution.
 // Context is placed only in an existing ordinary message field when still empty.
 const query=new URL(location.href).searchParams;
 document.querySelectorAll('[data-axis-context]').forEach(root=>{
  const field=root.querySelector('textarea');if(!field||field.value.trim())return;
  const context=query.get('context')||query.get('solution')||query.get('capability')||query.get('industry')||root.dataset.axisContext,material=query.get('material'),phase=query.get('phase');
  const lines=[];if(context)lines.push('Project context: '+context);if(phase)lines.push('Program phase: '+phase);if(material)lines.push('Material to discuss: '+material);
  if(lines.length){field.value=lines.join('\n')+'\n\nRequirements:\n';field.dispatchEvent(new Event('input',{bubbles:true}));field.dispatchEvent(new Event('change',{bubbles:true}))}
 });
})();
