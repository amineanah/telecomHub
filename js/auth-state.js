
import { auth } from "./firebase-config.js";
import {getFirestore,doc,getDoc} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-firestore.js";

import {
  onAuthStateChanged,
  signOut
} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";

const loginBtn =
  document.getElementById("login-link");

const logoutBtn =
  document.getElementById("logout-btn");

const userName =
  document.getElementById("user-name");

if (!loginBtn || !logoutBtn || !userName) {
  console.error(
    "TelecomHub: Login/Logout elements missing in index.html"
  );
} else {

  let photoRequest=0;
  onAuthStateChanged(auth, async (user) => {

    if (user) {

      // USER IS LOGGED IN

      loginBtn.hidden = true;
      logoutBtn.hidden = false;
      userName.hidden = false;

      userName.style.cursor = "pointer";
      userName.setAttribute("title","Open My Profile");
      userName.removeAttribute("role");
      userName.removeAttribute("tabindex");
      userName.onclick=null;
      userName.onkeydown=null;
      const label = document.createElement("span");
      label.textContent = user.displayName || user.email || "TelecomHub Member";
      const fallback = document.createElement("span");
      fallback.className = "home-profile-fallback";
      fallback.textContent = (user.displayName || user.email || "U")
        .trim().split(/\s+/).slice(0, 2).map(part => part[0] || "").join("").toUpperCase();
      userName.replaceChildren(fallback, label);
      const request = ++photoRequest;
      try {
        const snap = await getDoc(doc(getFirestore(), "users", user.uid, "profile", "avatar"));
        if (request === photoRequest && snap.exists() && snap.data().imageData) {
          const img = document.createElement("img");
          img.className = "home-profile-photo";
          img.src = snap.data().imageData;
          img.alt = "";
          fallback.replaceWith(img);
        }
      } catch (error) {
        console.warn("Profile picture unavailable; showing initials.", error);
      }

      console.log(
        "TelecomHub: User logged in",
        user.email
      );

    } else {

      // USER IS LOGGED OUT

      ++photoRequest;
      loginBtn.hidden = false;
      logoutBtn.hidden = true;
      userName.hidden = true;
      userName.textContent = "";

      console.log(
        "TelecomHub: No user logged in"
      );
    }

  }, (error) => {
    console.error("Firebase auth state error:", error);
  });

  logoutBtn.addEventListener("click", async () => {

    try {

      await signOut(auth);

      console.log("Logout successful");

      window.location.href = "/login.html";

    } catch (error) {

      console.error("Logout error:", error);

      alert(
        "Unable to logout. Please try again."
      );
    }

  });
}
