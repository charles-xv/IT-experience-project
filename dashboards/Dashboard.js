// Logout confirmation. Intercepts the log-out link and shows a themed modal
// instead of leaving immediately, so an accidental click doesn't end the session.
const logoutLink = document.querySelector(".dash-logout");
const modal = document.getElementById("logoutModal");
const confirmBtn = document.getElementById("logoutConfirm");
const cancelBtn = document.getElementById("logoutCancel");

if (logoutLink && modal) {
  const logoutUrl = logoutLink.getAttribute("href");

  logoutLink.addEventListener("click", (e) => {
    e.preventDefault();
    modal.classList.add("open");
  });

  cancelBtn.addEventListener("click", () => modal.classList.remove("open"));

  // Clicking the dimmed backdrop (but not the card) also cancels.
  modal.addEventListener("click", (e) => {
    if (e.target === modal) modal.classList.remove("open");
  });

  // Escape key cancels too.
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") modal.classList.remove("open");
  });

  confirmBtn.addEventListener("click", () => {
    window.location.href = logoutUrl;
  });
}
