
import { auth } from "./firebase-config.js";

import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  sendPasswordResetEmail,
  sendEmailVerification,
  updateProfile,
  signOut
} from "https://www.gstatic.com/firebasejs/12.4.0/firebase-auth.js";

const form = document.getElementById("auth-form");
const heading = document.getElementById("heading");
const intro = document.getElementById("intro");
const nameGroup = document.getElementById("name-group");
const nameInput = document.getElementById("name");
const emailInput = document.getElementById("email");
const passwordGroup =
  document.getElementById("password-group");
const passwordInput =
  document.getElementById("password");
const submitBtn = document.getElementById("submit");
const message = document.getElementById("message");
const switchBtn = document.getElementById("switch");
const forgotBtn = document.getElementById("forgot");

let mode = "login";

function setMode(newMode) {
  mode = newMode;
  form.reset();
  message.textContent = "";

  nameGroup.hidden = mode !== "signup";
  passwordGroup.hidden = mode === "reset";

  nameInput.required = mode === "signup";
  passwordInput.required = mode !== "reset";

  passwordInput.autocomplete =
    mode === "signup"
      ? "new-password"
      : "current-password";

  if (mode === "login") {
    heading.textContent = "Welcome Back";
    intro.textContent = "Sign in to TelecomHub";
    submitBtn.textContent = "Sign In";
    switchBtn.textContent = "Create Account";
    forgotBtn.hidden = false;
  }

  if (mode === "signup") {
    heading.textContent = "Create Account";
    intro.textContent = "Join TelecomHub";
    submitBtn.textContent = "Create Account";
    switchBtn.textContent = "Back to Login";
    forgotBtn.hidden = true;
  }

  if (mode === "reset") {
    heading.textContent = "Reset Password";
    intro.textContent = "Receive a password reset email";
    submitBtn.textContent = "Send Reset Email";
    switchBtn.textContent = "Back to Login";
    forgotBtn.hidden = true;
  }
}

switchBtn.addEventListener("click", () => {
  setMode(mode === "login" ? "signup" : "login");
});

forgotBtn.addEventListener("click", () => {
  setMode("reset");
});

form.addEventListener("submit", async (event) => {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value;
  const fullName = nameInput.value.trim();

  submitBtn.disabled = true;
  message.textContent = "Please wait...";

  try {

    if (mode === "signup") {

      if (!fullName || password.length < 8) {
        throw new Error(
          "Enter your name and a password of at least 8 characters."
        );
      }

      const result =
        await createUserWithEmailAndPassword(
          auth, email, password
        );

      try {
        await updateProfile(result.user, {
          displayName: fullName
        });

        await sendEmailVerification(result.user);

        message.textContent =
          "Account created! Check your email.";
      } catch (setupError) {
        console.error("Account setup:", setupError);
        message.textContent =
          "Account created, but verification or profile setup failed.";
      } finally {
        await signOut(auth);
      }

    } else if (mode === "login") {

      await signInWithEmailAndPassword(
        auth, email, password
      );

      window.location.href = "/";

    } else {

      await sendPasswordResetEmail(auth, email);

      message.textContent =
        "If an account exists, a reset email will be sent.";
    }

  } catch (error) {

    console.error("Firebase error:", error.code || error);

    message.textContent =
      error.message ||
      "Authentication failed. Please try again.";

  } finally {

    submitBtn.disabled = false;

  }
});

setMode("login");
