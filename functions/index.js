'use strict';
const {onCall,HttpsError}=require('firebase-functions/v2/https');
const {initializeApp}=require('firebase-admin/app');
const {getAuth}=require('firebase-admin/auth');
const {getFirestore,Timestamp,FieldValue}=require('firebase-admin/firestore');
const crypto=require('node:crypto');
initializeApp();
const db=getFirestore();
const ADMIN_UID='wq68WfHSlzW1SNCeyIC2uoNwYvd2';
const EXAMS=['5G NR Fundamentals','LTE / 4G Engineering','RAN Optimization','RF Planning & Design','Telecom Site Quality Control & Acceptance','Telecom EHS & Working at Heights','Fiber Optics & FTTH Engineering'];
function authorized(request){if(!request.auth||request.auth.uid!==ADMIN_UID)throw new HttpsError('permission-denied','Administrator access required.');}
function cleanEmail(v){if(typeof v!=='string'||v.length>254||!/^\S+@\S+\.\S+$/.test(v.trim()))throw new HttpsError('invalid-argument','Valid email required.');return v.trim().toLowerCase();}
async function findUser(email){try{return await getAuth().getUserByEmail(email);}catch(e){if(e.code==='auth/user-not-found')throw new HttpsError('not-found','No registered user with that email.');throw e;}}
exports.lookupCertificateRecoveryUser=onCall({region:'us-central1',maxInstances:5},async request=>{
 authorized(request);const email=cleanEmail(request.data?.email);const user=await findUser(email);
 const snap=await db.collection('users').doc(user.uid).collection('certificates').get();
 return {uid:user.uid,email:user.email,displayName:user.displayName||'',certificates:snap.docs.map(d=>({exam:d.get('exam')||'',certificateId:d.get('certificateId')||'',score:d.get('score')??null}))};
});
exports.restoreCertificateAdmin=onCall({region:'us-central1',maxInstances:5},async request=>{
 authorized(request);const input=request.data||{};const email=cleanEmail(input.email);
 if(typeof input.uid!=='string'||!input.uid||input.uid.length>128)throw new HttpsError('invalid-argument','Lookup the user first.');
 if(!EXAMS.includes(input.exam))throw new HttpsError('invalid-argument','Choose an Academy exam.');
 if(!Number.isInteger(input.score)||input.score<80||input.score>100)throw new HttpsError('invalid-argument','Passing score must be 80–100.');
 if(typeof input.certificateId!=='string'||! /^[a-zA-Z0-9-]{8,80}$/.test(input.certificateId))throw new HttpsError('invalid-argument','Invalid certificate ID.');
 if(typeof input.completedDate!=='string'||!/^\d{4}-\d{2}-\d{2}$/.test(input.completedDate))throw new HttpsError('invalid-argument','Completion date must be YYYY-MM-DD.');
 const date=new Date(input.completedDate+'T12:00:00Z');
 if(Number.isNaN(date.getTime())||date.toISOString().slice(0,10)!==input.completedDate||date>Date.now())throw new HttpsError('invalid-argument','Invalid or future date.');
 const user=await findUser(email);
 if(user.uid!==input.uid)throw new HttpsError('failed-precondition','User identity changed. Look up the user again.');
 const col=db.collection('users').doc(user.uid).collection('certificates');
 const ref=col.doc(input.certificateId);
 const audit=db.collection('certificateRecoveryAudit').doc();
 const result=await db.runTransaction(async tx=>{
   const existing=await tx.get(col);
   if(existing.docs.some(d=>d.get('exam')===input.exam||d.get('certificateId')===input.certificateId||d.id===input.certificateId))
     throw new HttpsError('already-exists','Certificate already exists for this exam or ID.');
   // Restored from documentary evidence, not re-scored. Exact correct answers unknown.
   tx.create(ref,{candidate:user.displayName||input.candidate||email,exam:input.exam,score:input.score,certificateId:input.certificateId,issuedAt:Timestamp.fromDate(date),verificationStatus:'unverified',source:'admin-recovery',recoveredAt:FieldValue.serverTimestamp()});
   tx.create(audit,{action:'restore-certificate',adminUid:request.auth.uid,targetUid:user.uid,targetEmail:email,exam:input.exam,score:input.score,certificateId:input.certificateId,completedDate:input.completedDate,reason:typeof input.reason==='string'?input.reason.slice(0,500):'',createdAt:FieldValue.serverTimestamp()});
   return {certificateId:input.certificateId,auditId:audit.id};
 });
 return {ok:true,...result};
});

