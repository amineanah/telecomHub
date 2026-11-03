// Cloudflare Worker: scheduled verified-certificate email delivery.
// Configure SERVICE_ACCOUNT_JSON, RESEND_API_KEY, RESEND_FROM as Worker secrets.
const PROJECT='telecomhu-1c41c';
const enc=o=>btoa(String.fromCharCode(...new TextEncoder().encode(typeof o==='string'?o:JSON.stringify(o)))).replaceAll('+','-').replaceAll('/','_').replace(/=+$/,'');
const escapeHtml=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function accessToken(env){
 const sa=JSON.parse(env.SERVICE_ACCOUNT_JSON),now=Math.floor(Date.now()/1000);
 const head=enc({alg:'RS256',typ:'JWT'}),payload=enc({iss:sa.client_email,scope:'https://www.googleapis.com/auth/cloud-platform',aud:'https://oauth2.googleapis.com/token',iat:now,exp:now+3500});
 const raw=Uint8Array.from(atob(sa.private_key.replace(/-----[^-]+-----/g,'').replace(/\\s/g,'')),c=>c.charCodeAt(0));
 const key=await crypto.subtle.importKey('pkcs8',raw,{name:'RSASSA-PKCS1-v1_5',hash:'SHA-256'},false,['sign']);
 const signature=await crypto.subtle.sign('RSASSA-PKCS1-v1_5',key,new TextEncoder().encode(head+'.'+payload));
 const sig=btoa(String.fromCharCode(...new Uint8Array(signature))).replace(/\\+/g,'-').replace(/\\//g,'_').replace(/=+$/,'');
 const res=await fetch('https://oauth2.googleapis.com/token',{method:'POST',headers:{'content-type':'application/x-www-form-urlencoded'},body:new URLSearchParams({grant_type:'urn:ietf:params:oauth:grant-type:jwt-bearer',assertion:head+'.'+payload+'.'+sig})});
 if(!res.ok)throw Error('OAuth failed '+res.status);
 return(await res.json()).access_token;
}
const base='https://firestore.googleapis.com/v1/projects/'+PROJECT+'/databases/(default)/documents';
const field=(value)=>value?.stringValue??value?.integerValue??value?.doubleValue??'';
async function api(url,token,options={}){const res=await fetch(url,{...options,headers:{authorization:'Bearer '+token,'content-type':'application/json',...(options.headers||{})}});if(!res.ok){if(res.status===404)return null;throw Error('Firestore API '+res.status+' '+(await res.text()).slice(0,180))}return res.json()}
async function learnerEmail(uid,token){
 const res=await fetch('https://identitytoolkit.googleapis.com/v1/projects/'+PROJECT+'/accounts:lookup',{method:'POST',headers:{authorization:'Bearer '+token,'content-type':'application/json'},body:JSON.stringify({localId:[uid]})});
 if(!res.ok)throw Error('Auth lookup '+res.status);
 const user=(await res.json()).users?.[0];return user?.emailVerified&&user.email?user.email:null;
}
async function run(env){
 if(!env.SERVICE_ACCOUNT_JSON||!env.RESEND_API_KEY||!env.RESEND_FROM)throw Error('Missing required Worker secrets');
 const token=await accessToken(env);
 const query={structuredQuery:{from:[{collectionId:'certificates',allDescendants:true}],where:{fieldFilter:{field:{fieldPath:'verificationStatus'},op:'EQUAL',value:{stringValue:'verified'}}},limit:250}};
 const res=await fetch(base+':runQuery',{method:'POST',headers:{authorization:'Bearer '+token,'content-type':'application/json'},body:JSON.stringify(query)});
 if(!res.ok)throw Error('Certificate query '+res.status+' '+(await res.text()).slice(0,180));
 const items=await res.json();let sent=0,skipped=0;
 for(const item of items){const d=item.document;if(!d)continue;const path=d.name.split('/documents/')[1],parts=path.split('/');if(parts.length!==4||parts[0]!=='users'||parts[2]!=='certificates'){skipped++;continue}
 const uid=parts[1],f=d.fields||{},id=String(field(f.certificateId)||parts[3]),name=String(field(f.candidate)||'Learner'),exam=String(field(f.exam)||'Telecom examination'),score=field(f.score);
 // Deterministic ID per certificate; duplicate runs use the same provider idempotency key.
 const digest=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(path));const logId=Array.from(new Uint8Array(digest)).map(b=>b.toString(16).padStart(2,'0')).join('');
 const logUrl=base+'/verificationEmails/'+logId;
 const existing=await api(logUrl,token);if(existing){skipped++;continue}
 const to=await learnerEmail(uid,token);if(!to){skipped++;continue}
 const link='https://www.telcomdigitalhub.com/profile.html';
 const html='<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#152338"><h1 style="color:#075b9a">Certificate Verified</h1><p>Dear '+escapeHtml(name)+',</p><p>Your educational achievement certificate has been reviewed and <strong>verified by Telecom Digital Hub</strong>.</p><p><strong>Examination:</strong> '+escapeHtml(exam)+'<br><strong>Certificate ID:</strong> '+escapeHtml(id)+'<br><strong>Score:</strong> '+escapeHtml(score)+'%<br><strong>Status:</strong> VERIFIED</p><p><a href="'+link+'">Sign in to view and print your certificate</a></p><p>This is verification by Telecom Digital Hub, not independent industry accreditation.</p><p>Telecom Digital Hub Team</p></div>';
 const mail=await fetch('https://api.resend.com/emails',{method:'POST',headers:{authorization:'Bearer '+env.RESEND_API_KEY,'content-type':'application/json','Idempotency-Key':'tdh-verified-'+logId.slice(0,48)},body:JSON.stringify({from:env.RESEND_FROM,to:[to],subject:'Your Telecom Digital Hub certificate is verified',html})});
 if(!mail.ok){console.error('Email provider rejected certificate',id,mail.status);continue}
 const result=await mail.json();
 // Log only non-sensitive delivery metadata, never email addresses.
 await api(base+'/verificationEmails?documentId='+logId,token,{method:'POST',body:JSON.stringify({fields:{certificatePath:{stringValue:path},certificateId:{stringValue:id},providerMessageId:{stringValue:result.id||''},sentAt:{timestampValue:new Date().toISOString()}}})});
 sent++;
 }
 console.log('Verification email sweep:',sent,'sent,',skipped,'skipped');
}
export default{
 async scheduled(event,env,ctx){ctx.waitUntil(run(env))},
 async fetch(){return new Response('Verified-certificate email service is running. Delivery uses scheduled checks.',{status:200,headers:{'content-type':'text/plain'}})}
};
