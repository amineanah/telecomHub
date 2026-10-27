
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
    "TelecomHub: Login/Logout elements missing in index.html"
  );
} else {

  onAuthStateChanged(auth, (user) => {

    if (user) {

      // USER IS LOGGED IN

      loginBtn.hidden = true;
      logoutBtn.hidden = false;
      userName.hidden = false;

      userName.textContent =
        user.displayName ||
        user.email ||
        "TelecomHub Member";

      console.log(
        "TelecomHub: User logged in",
        user.email
      );

    } else {

      // USER IS LOGGED OUT

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
