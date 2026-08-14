// ============================================================
// SIGNUP ROLE TOGGLE
// ============================================================

const roleInput = document.getElementById("roleInput");
const roleOptions = document.querySelectorAll(".role-option");

// Dynamic signup text elements
const signupTitle = document.querySelector(".auth-form-panel h2");
const signupSubtitle = document.querySelector(".auth-subtitle");
const authAsideText = document.querySelector(".auth-aside p");
const signupButton = document.querySelector(
  '.auth-form-panel button[type="submit"]'
);

if (roleInput && roleOptions.length) {

  const selectRole = (role) => {

    // Store selected role in hidden form field
    roleInput.value = role;

    // Highlight selected role
    roleOptions.forEach((opt) => {
      opt.classList.toggle(
        "active",
        opt.dataset.role === role
      );
    });

    // ========================================================
    // STUDENT
    // ========================================================

    if (role === "student") {

      if (signupTitle) {
        signupTitle.textContent = "Create your account";
      }

      if (signupSubtitle) {
        signupSubtitle.textContent =
          "Free to join, no card required";
      }

      if (authAsideText) {
        authAsideText.textContent =
          "Learn at your own pace from anywhere.";
      }

      if (signupButton) {
        signupButton.textContent = "Create account";
      }

    }

    // ========================================================
    // INSTRUCTOR
    // ========================================================

    else if (role === "instructor") {

      if (signupTitle) {
        signupTitle.textContent =
          "Create your account";
      }

      if (signupSubtitle) {
        signupSubtitle.textContent =
          "Free to join, publish your first course in minutes";
      }

      if (authAsideText) {
        authAsideText.textContent =
          "Share what you know and start teaching today.";
      }

      if (signupButton) {
        signupButton.textContent =
          "Create instructor account";
      }
    }
  };

  // Clicking Student / Instructor
  roleOptions.forEach((opt) => {

    opt.addEventListener("click", () => {
      selectRole(opt.dataset.role);
    });

  });

  // Set the correct content when the page first loads
  selectRole(roleInput.value || "student");
}


// ============================================================
// PASSWORD SHOW / HIDE TOGGLE
// ============================================================

const passwordInput = document.getElementById("password");
const passwordToggle = document.getElementById("passwordToggle");

if (passwordInput && passwordToggle) {

  passwordToggle.addEventListener("click", () => {

    const showing = passwordInput.type === "text";

    passwordInput.type = showing
      ? "password"
      : "text";

    passwordToggle.classList.toggle(
      "revealed",
      !showing
    );

    passwordToggle.setAttribute(
      "aria-label",
      showing
        ? "Show password"
        : "Hide password"
    );

  });

}