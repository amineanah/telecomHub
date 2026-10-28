import {auth} from "./firebase-config.js";
import {getFirestore,collection,query,where,getDocs,limit} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js";
const grid=document.getElementById("hub-card-grid");
const status=document.getElementById("hub-dynamic-status");
if(grid){
  const labels={"5g":"5G Networks",ran:"RAN & Radio",fiber:"Fiber Optics",ehs:"EHS & Quality",site:"Site Engineering",ai:"AI & Automation",tools:"Tools & Practical",core:"Core & Transport",power:"Power & IoT"};
  const db=getFirestore(auth.app);
  function safeImage(url){
    if(typeof url!=="string"||!url)return "";
    try{const parsed=new URL(url);return parsed.protocol==="https:"?parsed.href:"";}catch{return "";}
  }
  function addCard(id,data){
    if(!data||typeof data.title!=="string"||typeof data.summary!=="string"||!(data.category in labels))return;
    const card=document.createElement("article");
    card.className="hub-card";card.dataset.hubCategory=data.category;
    card.dataset.hubSearch=(data.title+" "+data.summary+" "+labels[data.category]).toLocaleLowerCase();
    const link=document.createElement("a");link.className="hub-card-link";link.href="article.html?id="+encodeURIComponent(id);
    link.setAttribute("aria-label",data.title);
    const media=document.createElement("div");media.className="hub-card-media";
    const cover=safeImage(data.coverUrl);
    if(cover){const img=document.createElement("img");img.src=cover;img.alt="Cover image for "+data.title;img.loading="lazy";media.append(img);}
    else{media.classList.add("hub-placeholder");const mark=document.createElement("span");mark.className="hub-placeholder-mark";mark.textContent="TELECOM HUB";media.append(mark);}
    const tag=document.createElement("span");tag.className="hub-card-category";tag.textContent=labels[data.category];media.append(tag);
    const content=document.createElement("div");content.className="hub-card-content";
    const type=document.createElement("span");type.className="hub-card-type";type.textContent="TelecomHub article";
    const title=document.createElement("h3");title.textContent=data.title;
    const desc=document.createElement("p");desc.textContent=data.summary;
    const action=document.createElement("span");action.className="hub-card-action";action.textContent="Read article →";
    content.append(type,title,desc,action);link.append(media,content);card.append(link);grid.prepend(card);
  }
  try{
    const snap=await getDocs(query(collection(db,"articles"),where("status","==","published"),limit(100)));
    const docs=snap.docs.map(d=>({id:d.id,data:d.data()}));
    docs.sort((a,b)=>(a.data.createdAt?.seconds||0)-(b.data.createdAt?.seconds||0));
    docs.forEach(d=>addCard(d.id,d.data));
    if(status)status.textContent=docs.length?docs.length+" new article(s) published by TelecomHub.":"";
  }catch(e){console.error("TelecomHub article listing failed",e);if(status)status.textContent="New articles are temporarily unavailable.";}
  document.dispatchEvent(new Event("telecom:articles-loaded"));
}