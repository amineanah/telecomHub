import {auth} from "./firebase-config.js";
import {onAuthStateChanged} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";
import {getFirestore,collection,getDocs,doc,addDoc,updateDoc,deleteDoc,serverTimestamp} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js";
import {getStorage,ref,uploadBytes,getDownloadURL} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-storage.js";
const ADMIN_UID="wq68WfHSlzW1SNCeyIC2uoNwYvd2",db=getFirestore(auth.app),storage=getStorage(auth.app);
const $=id=>document.getElementById(id),form=$("article-form"),items=$("items"),notice=$("notice");
const labels={ "5g":"5G Networks",ran:"RAN & Radio",fiber:"Fiber Optics",ehs:"EHS & Quality",site:"Site Engineering",ai:"AI & Automation",tools:"Tools & Practical",core:"Core & Transport",power:"Power & IoT"};
let editing=null,media=[],articles=[],authorized=false,busy=false;
function message(s,error=false){notice.textContent=s;notice.classList.toggle("error",error);}
function lock(v){busy=v;document.querySelectorAll("#manager button").forEach(b=>b.disabled=v);}
function clearForm(){editing=null;media=[];form.reset();$("editor-title").textContent="Write a new article";$("body-count").textContent="0";renderMedia();}
function button(text,cls,action){const b=document.createElement("button");b.type="button";b.textContent=text;b.className=cls||"";b.addEventListener("click",action);return b;}
function validHttps(raw){try{const u=new URL(raw);return u.protocol==="https:"&&raw.length<=1500;}catch{return false;}}
function videoId(raw){try{const u=new URL(raw);if(u.hostname==="youtu.be")return /^[\w-]{11}$/.test(u.pathname.slice(1))?u.pathname.slice(1):null;if(["youtube.com","www.youtube.com","m.youtube.com","www.youtube-nocookie.com"].includes(u.hostname)){const id=u.searchParams.get("v")||u.pathname.match(/^\/(?:embed|shorts)\/([\w-]{11})/)?.[1];return /^[\w-]{11}$/.test(id||"")?id:null;}}catch{}return null;}
function renderMedia(){
const target=$("media-items");target.replaceChildren();
for(const [i,m] of media.entries()){const p=document.createElement("div");p.className="article-item";
const label=document.createElement("strong");label.textContent=m.type.toUpperCase()+" · "+m.title;
const link=document.createElement("a");link.href=m.url;link.target="_blank";link.rel="noopener noreferrer";link.textContent=" Preview ↗";link.style.marginLeft="10px";
p.append(label,link,button("Remove","danger",()=>{media.splice(i,1);renderMedia();}));target.append(p);}
}
function addMedia(type,title,url){title=title.trim();url=url.trim();
if(!["image","youtube","video","pdf","link"].includes(type)||title.length<2||title.length>120||!validHttps(url)||media.length>=20||(type==="youtube"&&!videoId(url))){message("Invalid media. Enter a label, a valid HTTPS URL (or YouTube video link), and limit to 20 attachments.",true);return false;}
media.push({type,title,url});renderMedia();message("Media attached to the editor. Save the article to keep this change.");return true;}
function values(){
if(!form.reportValidity())return null;
const data={title:$("article-title").value.trim(),category:$("article-category").value,summary:$("article-summary").value.trim(),body:$("article-body").value.trim(),coverUrl:$("article-cover").value.trim(),media:media.map(x=>({...x}))};
if(data.title.length<5||data.title.length>160||!(data.category in labels)||data.summary.length<25||data.summary.length>500||data.body.length<100||data.body.length>30000||(data.coverUrl&&!validHttps(data.coverUrl))){message("Check the title, category, summary, article text, and optional HTTPS cover image.",true);return null;}
return data;
}
async function save(status){
if(!authorized||busy)return;const data=values();if(!data)return;lock(true);message("Saving article…");
try{
if(editing)await updateDoc(doc(db,"articles",editing),{...data,status,updatedAt:serverTimestamp()});
else await addDoc(collection(db,"articles"),{...data,status,createdAt:serverTimestamp(),updatedAt:serverTimestamp()});
clearForm();message(status==="published"?"Published! The article is now available to visitors.":"Draft saved privately.");await load();
}catch(e){console.error(e);message("Save failed: "+(e.code||e.message),true);}
finally{lock(false);}
}
function edit(item){editing=item.id;const d=item.data;
$("article-title").value=d.title||"";$("article-category").value=d.category||"5g";$("article-summary").value=d.summary||"";$("article-body").value=d.body||"";$("article-cover").value=d.coverUrl||"";
media=Array.isArray(d.media)?d.media.filter(m=>m&&["image","youtube","video","pdf","link"].includes(m.type)&&typeof m.url==="string"&&typeof m.title==="string").map(m=>({type:m.type,title:m.title,url:m.url})):[];renderMedia();$("body-count").textContent=String($("article-body").value.length);$("editor-title").textContent="Edit article";
message("Editing: "+d.title);window.scrollTo({top:0,behavior:"smooth"});$("article-title").focus();}
async function setStatus(item,status){if(busy)return;lock(true);try{await updateDoc(doc(db,"articles",item.id),{status,updatedAt:serverTimestamp()});message(status==="published"?"Article published.":"Article unpublished.");await load();}catch(e){message("Update failed: "+(e.code||e.message),true);}finally{lock(false);}}
async function remove(item){if(busy||!confirm("Permanently delete '"+item.data.title+"'? Uploaded files must be deleted separately from Firebase Storage."))return;lock(true);try{await deleteDoc(doc(db,"articles",item.id));if(editing===item.id)clearForm();message("Article deleted.");await load();}catch(e){message("Delete failed: "+(e.code||e.message),true);}finally{lock(false);}}
function render(){
items.replaceChildren();if(!articles.length){const p=document.createElement("p");p.textContent="No articles yet. Use the editor to create the first one.";items.append(p);}
for(const item of articles){const d=item.data,a=document.createElement("article"),pill=document.createElement("span"),title=document.createElement("h3"),description=document.createElement("p"),actions=document.createElement("div");
a.className="article-item";pill.className="pill "+(d.status==="published"?"published":"");pill.textContent=d.status==="published"?"Published":"Draft";title.textContent=d.title||"Untitled";description.textContent=(labels[d.category]||"Other")+" · "+(d.summary||"");actions.className="actions";
actions.append(button("Edit","secondary",()=>edit(item)),button(d.status==="published"?"Unpublish":"Publish",d.status==="published"?"secondary":"success",()=>setStatus(item,d.status==="published"?"draft":"published")),button("Delete","danger",()=>remove(item)));
a.append(pill,title,description,actions);items.append(a);}
}
async function load(){if(!authorized)return;try{const snap=await getDocs(collection(db,"articles"));articles=snap.docs.map(d=>({id:d.id,data:d.data()})).sort((a,b)=>(b.data.updatedAt?.seconds||0)-(a.data.updatedAt?.seconds||0));render();}catch(e){console.error(e);message("Could not list articles. Check Firestore rules: "+(e.code||e.message),true);}}
$("save-draft").onclick=()=>save("draft");$("publish").onclick=()=>save("published");$("reset-form").onclick=clearForm;$("refresh").onclick=load;
$("article-body").addEventListener("input",()=>{$("body-count").textContent=String($("article-body").value.length);});
$("add-media").onclick=()=>{if(addMedia($("media-type").value,$("media-title").value,$("media-url").value)){$("media-title").value="";$("media-url").value="";}};
$("upload-media").onclick=async()=>{
if(!authorized||busy)return;const file=$("media-file").files?.[0];if(!file){message("Choose a file first.",true);return;}
const allowed={"image/jpeg":"image","image/png":"image","image/webp":"image","application/pdf":"pdf","video/mp4":"video","video/webm":"video"};
const type=allowed[file.type];if(!type||file.size>25*1024*1024||file.size===0){message("Use JPG, PNG, WebP, PDF, MP4 or WebM, up to 25 MB.",true);return;}
if(media.length>=20){message("Maximum 20 attachments per article.",true);return;}
lock(true);message("Uploading media to Firebase Storage…");
try{
const safeName=file.name.replace(/[^a-zA-Z0-9._-]/g,"_").slice(-100);
const path="article-media/"+ADMIN_UID+"/"+crypto.randomUUID()+"-"+safeName;
const uploaded=await uploadBytes(ref(storage,path),file,{contentType:file.type});
const url=await getDownloadURL(uploaded.ref);
media.push({type,title:file.name.slice(0,120),url});renderMedia();$("media-file").value="";
message("Uploaded successfully. Save or publish the article to attach the file.");
}catch(e){console.error(e);message("Upload failed. Enable Firebase Storage and deploy storage.rules first. "+(e.code||e.message),true);}
finally{lock(false);}
};
onAuthStateChanged(auth,user=>{
authorized=Boolean(user&&user.uid===ADMIN_UID);$("manager").hidden=!authorized;
if(!user)message("Please sign in using your administrator account before opening this page.",true);
else if(!authorized)message("Access denied. Your account is not the designated TelecomHub administrator.",true);
else{message("Administrator authenticated. Ready to manage articles.");load();}
});
