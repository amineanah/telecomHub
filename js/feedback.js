import { auth } from "./firebase-config.js";
import { getFirestore, collection, addDoc, getDocs, query, where, serverTimestamp, limit } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";
const db=getFirestore(auth.app);
const form=document.getElementById("telecom-feedback-form");
const list=document.getElementById("telecom-feedback-list");
const status=document.getElementById("telecom-feedback-status");
const submit=form?.querySelector('button[type="submit"]');
let currentUser=null;
onAuthStateChanged(auth,user=>{currentUser=user;if(submit)submit.disabled=!user;if(status)status.textContent=user?"Your feedback will be reviewed before publication.":"Please log in to submit feedback.";});
function feedbackCard(data){
const article=document.createElement("article");article.className="telecom-feedback-card";
const h=document.createElement("strong");h.textContent=data.name||"TelecomHub visitor";
const stars=document.createElement("span");stars.textContent="★".repeat(Math.min(5,Math.max(1,Number(data.rating)||5)));stars.className="telecom-feedback-stars";
const p=document.createElement("p");p.textContent=data.message||"";
article.append(h,stars,p);return article;
}
async function loadFeedback(){
try{
const snap=await getDocs(query(collection(db,"visitorFeedback"),where("status","==","approved"),limit(30)));
if(!list)return;list.replaceChildren();
if(snap.empty){list.textContent="No approved feedback yet. Be the first to share your experience.";return;}
const docs=snap.docs.map(x=>x.data()).sort((a,b)=>(b.createdAt?.seconds||0)-(a.createdAt?.seconds||0));
docs.forEach(d=>list.appendChild(feedbackCard(d)));
}catch(error){console.error(error);if(list)list.textContent="Unable to load feedback right now.";}
}
form?.addEventListener("submit",async event=>{
event.preventDefault();
if(!currentUser){location.href="/login.html";return;}
const name=form.elements.namedItem("visitorName").value.trim();
const message=form.elements.namedItem("feedbackMessage").value.trim();
const rating=Number(form.elements.namedItem("feedbackRating").value);
if(!name||name.length>60||message.length<10||message.length>1000||rating<1||rating>5){status.textContent="Please enter a name, a rating and a message of 10–1000 characters.";return;}
submit.disabled=true;
try{
await addDoc(collection(db,"visitorFeedback"),{name,message,rating,status:"pending",authorUid:currentUser.uid,createdAt:serverTimestamp()});
form.reset();status.textContent="Thank you! Your feedback is pending administrator approval.";
}catch(error){console.error(error);status.textContent="Submission failed. Check your Firebase permissions and try again.";}
finally{submit.disabled=!currentUser;}
});
loadFeedback();
