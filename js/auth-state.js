
import { auth } from "./firebase-config.js";

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
    "TelecomHub: Authentication navigation elements missing."
  );
} else {
  onAuthStateChanged(auth, (user) => {

    if (user) {
      // User is logged in
      loginBtn.hidden = true;
      logoutBtn.hidden = false;
      userName.hidden = false;

      userName.textContent =
        user.displayName || user.email || "Member";

    } else {
      // User is logged out
      loginBtn.hidden = false;
      logoutBtn.hidden = true;
      userName.hidden = true;
      userName.textContent = "";
    }

  }, (error) => {
    console.error("Auth state error:", error);
    loginBtn.hidden = false;
    logoutBtn.hidden = true;
    userName.hidden = true;
  });

  logoutBtn.addEventListener("click", async () => {
    try {
      await signOut(auth);
      window.location.href = "/login.html";
    } catch (error) {
      console.error("Logout failed:", error);
      alert("Logout failed. Please try again.");
    }
  });
}
