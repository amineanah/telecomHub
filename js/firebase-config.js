
import { initializeApp } from
  "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";

import { getAuth } from
  "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyC9lGPBtvYcIVvMsoyUrWjNHWh-veypVIo",
  authDomain: "telecomhu-1c41c.firebaseapp.com",
  databaseURL: "https://telecomhu-1c41c-default-rtdb.firebaseio.com",
  projectId: "telecomhu-1c41c",
  storageBucket: "telecomhu-1c41c.firebasestorage.app",
  messagingSenderId: "795179788329",
  appId: "1:795179788329:web:b95371b57ac24e071bd438",
  measurementId: "G-VJP548NTZR"
};

const app = initializeApp(firebaseConfig);

export const auth = getAuth(app);
