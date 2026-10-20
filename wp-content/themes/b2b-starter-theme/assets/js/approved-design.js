(() => {
 document.querySelectorAll('.ar-filters').forEach(root=>{
  const cards=[...root.parentElement.querySelectorAll('[data-grade-tag]')];
  root.querySelectorAll('button').forEach(button=>button.addEventListener('click',()=>{root.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));cards.forEach(card=>card.hidden=button.dataset.gradeFilter!=='all'&&card.dataset.gradeTag!==button.dataset.gradeFilter);}));
 });
 document.querySelectorAll('.ar-compact-form form').forEach(form=>{
  const extra=['company','country','inquiry_attachments'].map(name=>form.querySelector(`[name^="${name}"]`)?.closest('.ff-el-group')).filter(Boolean);
  if(extra.length){const details=document.createElement('details');details.className='ar-optional-fields';const summary=document.createElement('summary');summary.textContent='Company, location or files (optional)';details.append(summary);const message=form.querySelector('textarea')?.closest('.ff-el-group');message?.after(details);extra.forEach(group=>details.append(group));}
 });
})();
