const $=id=>document.getElementById(id);
const safeCount=n=>Number.isFinite(n)?n.toLocaleString('en-GB'):'0';
export function renderLearningAnalytics(records,courses){
 const root=$('analytics-panel');if(!root)return;
 const unique=new Set(records.map(r=>r.uid).filter(Boolean)).size;
 const completed=records.filter(r=>(r.completed||[]).length>=12).length;
 const activity=records.filter(r=>{const t=r.updatedAt?.toDate?.();return t&&Date.now()-t.getTime()<=30*86400000}).length;
 $('analytics-enrollments').textContent=safeCount(records.length);
 $('analytics-learners').textContent=safeCount(unique);
 $('analytics-completions').textContent=safeCount(completed);
 $('analytics-active').textContent=safeCount(activity);
 const list=$('analytics-course-list');list.replaceChildren();
 const counts=courses.map(c=>({name:c.title,count:records.filter(r=>r.courseId===c.id).length})).sort((a,b)=>b.count-a.count);
 const max=Math.max(1,...counts.map(x=>x.count));
 for(const c of counts){const item=document.createElement('div');item.className='analytics-bar-item';const top=document.createElement('div');top.className='analytics-bar-label';const name=document.createElement('span');name.textContent=c.name;const count=document.createElement('strong');count.textContent=String(c.count);top.append(name,count);const track=document.createElement('div');track.className='analytics-bar-track';const bar=document.createElement('div');bar.className='analytics-bar-fill';bar.style.width=(c.count/max*100)+'%';track.append(bar);item.append(top,track);list.append(item)}
 $('analytics-updated').textContent='Learning data refreshed '+new Date().toLocaleString('en-GB')+'.';
}
export function initAnalytics(){const btn=$('analytics-ga-link');if(btn)btn.href='https://analytics.google.com/analytics/web/';}
