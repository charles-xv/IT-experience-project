// Role toggle on the signup page. Clicking a card highlights it and writes the
// chosen role into the hidden field the form submits. Guarded so it's skipped on
// the login page, which has no role toggle — without this guard the null lookup
// would throw and stop every script below it (including the password eye).
const roleInput = document.getElementById("roleInput");
const roleOptions = document.querySelectorAll(".role-option");

if (roleInput && roleOptions.length) {
  const selectRole = (role) => {
    roleInput.value = role;
    roleOptions.forEach((opt) => {
      opt.classList.toggle("active", opt.dataset.role === role);
    });
  };

  roleOptions.forEach((opt) => {
    opt.addEventListener("click", () => selectRole(opt.dataset.role));
  });

  selectRole(roleInput.value || "student");
}

// Password show/hide toggle. Flips the input between password and text, and
// swaps the open/closed eye icon. The role toggle above only exists on signup,
// but this runs on both pages since both have a password field.
const passwordInput = document.getElementById("password");
const passwordToggle = document.getElementById("passwordToggle");

if (passwordInput && passwordToggle) {
  passwordToggle.addEventListener("click", () => {
    const showing = passwordInput.type === "text";
    passwordInput.type = showing ? "password" : "text";
    passwordToggle.classList.toggle("revealed", !showing);
    passwordToggle.setAttribute(
      "aria-label",
      showing ? "Show password" : "Hide password",
    );
  });
}
