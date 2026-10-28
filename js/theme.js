/* Shared TelecomHub theme controller.
   Existing homepage/article toggles keep working; a switch is added to pages without one. */
(function(){
  "use strict";
  const key="theme";
  const path=decodeURIComponent(location.pathname.split("/").pop()||"index.html");
  const darkOriginal=new Set(["login.html","admin-articles.html","virtual-telecom-site.html.html"]);
  let saved=null;
  try{saved=localStorage.getItem(key);}catch(_){}
  const mode=saved==="dark"||saved==="light"?saved:"light";
  const root=document.documentElement;
  root.dataset.telecomTheme=mode;
  root.dataset.page=path;
  root.classList.toggle("theme-dark-original",darkOriginal.has(path));
  function setMode(value,persist){
    const next=value==="dark"?"dark":"light";
    root.dataset.telecomTheme=next;
    document.body.classList.toggle("dark-mode",next==="dark");
    if(persist){try{localStorage.setItem(key,next);}catch(_){}}
    root.style.colorScheme=next;
    const toggle=document.getElementById("themeToggle")||document.getElementById("telecom-global-theme");
    if(toggle){
      toggle.textContent=next==="dark"?"☀️":"🌙";
      toggle.setAttribute("aria-label",next==="dark"?"Switch to light mode":"Switch to dark mode");
      toggle.setAttribute("aria-pressed",String(next==="dark"));
      toggle.title=next==="dark"?"Switch to light mode":"Switch to dark mode";
    }
  }
  function start(){
    // Ensure light/dark is applied before the existing page toggle's saved state.
    setMode(root.dataset.telecomTheme,false);
    const existing=document.getElementById("themeToggle");
    if(existing){
      // Older pages already attach a click handler; refresh shared styling afterward.
      existing.addEventListener("click",function(){
        queueMicrotask(function(){
          const current=document.body.classList.contains("dark-mode")?"dark":"light";
          setMode(current,true);
        });
      });
    }else{
      const b=document.createElement("button");
      b.id="telecom-global-theme";
      b.type="button";
      document.body.append(b);
      b.addEventListener("click",()=>setMode(root.dataset.telecomTheme==="dark"?"light":"dark",true));
    }
    setMode(root.dataset.telecomTheme,false);
    window.addEventListener("storage",e=>{if(e.key===key&&["light","dark"].includes(e.newValue))setMode(e.newValue,false);});
  }
  if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",start,{once:true});else start();
})();
