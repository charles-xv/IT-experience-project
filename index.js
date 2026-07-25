// Cycle the security shield status text so the panel feels live
const shieldMessages = [
  "Verifying secure session\u2026",
  "RBAC role check passed",
  "No brute-force activity detected",
  "Session secured \u2713",
];
let shieldIdx = 0;
const shieldEl = document.getElementById("shieldStatus");
if (shieldEl) {
  setInterval(() => {
    shieldIdx = (shieldIdx + 1) % shieldMessages.length;
    shieldEl.style.opacity = "0";
    setTimeout(() => {
      shieldEl.textContent = shieldMessages[shieldIdx];
      shieldEl.style.opacity = "1";
    }, 250);
  }, 3200);
}

// Filter Courses
function filterCourses(cat, btn) {
  document
    .querySelectorAll(".filter-btn")
    .forEach((b) => b.classList.remove("active"));
  btn.classList.add("active");
  const cards = document.querySelectorAll(".course-card");
  cards.forEach((card) => {
    if (cat === "all" || card.dataset.cat === cat) {
      card.style.display = "flex";
    } else {
      card.style.display = "none";
    }
  });
}

// Billing Toggle
function toggleBilling() {
  const isAnnual = document.getElementById("billingCheck").checked;
  document.getElementById("proPrice").textContent = isAnnual ? "$15" : "$19";
  document.getElementById("proPeriod").textContent = isAnnual
    ? "/ month (billed annually)"
    : "/ month";
}

// AI Chat Bot
function toggleAIChat() {
  const box = document.getElementById("aiChatBox");
  box.classList.toggle("open");
}

function sendAIMessage() {
  const input = document.getElementById("aiInput");
  const text = input.value.trim();
  if (!text) return;

  const body = document.getElementById("aiChatBody");

  // User message
  const uMsg = document.createElement("div");
  uMsg.className = "ai-msg user";
  uMsg.textContent = text;
  body.appendChild(uMsg);

  input.value = "";
  body.scrollTop = body.scrollHeight;

  // Bot answer simulation in English
  setTimeout(() => {
    const bMsg = document.createElement("div");
    bMsg.className = "ai-msg bot";

    const lower = text.toLowerCase();
    if (
      lower.includes("register") ||
      lower.includes("account") ||
      lower.includes("sign up")
    ) {
      bMsg.textContent =
        "To register, click the 'Sign up' button at the top right, enter your email address and a strong password.";
    } else if (
      lower.includes("buy") ||
      lower.includes("course") ||
      lower.includes("pay") ||
      lower.includes("purchase")
    ) {
      bMsg.textContent =
        "To purchase a course, browse our catalog, click 'Enroll Now', and follow the simulated checkout process.";
    } else if (lower.includes("password")) {
      bMsg.textContent =
        "You can evaluate password strength during registration via our real-time visual strength meter.";
    } else {
      bMsg.textContent =
        "I am the Mech Spec support assistant. Feel free to ask me any questions about registration, courses, or navigation!";
    }

    body.appendChild(bMsg);
    body.scrollTop = body.scrollHeight;
  }, 600);
}

// Auth Modal & Password Strength
function openModal(type) {
  const overlay = document.getElementById("modalOverlay");
  const title = document.getElementById("modalTitle");
  const btn = document.getElementById("modalSubmitBtn");

  if (type === "login") {
    title.textContent = "Log in";
    btn.textContent = "Log in";
  } else {
    title.textContent = "Sign up";
    btn.textContent = "Create Account";
  }

  overlay.classList.add("active");
}

function closeModal() {
  document.getElementById("modalOverlay").classList.remove("active");
}

function checkStrength(val) {
  const bar = document.getElementById("strengthBar");
  let score = 0;
  if (val.length >= 6) score += 33;
  if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score += 34;
  if (/[^A-Za-z0-9]/.test(val) && val.length >= 8) score += 33;

  bar.style.width = score + "%";
  if (score <= 33) bar.style.backgroundColor = "#ef4444";
  else if (score <= 67) bar.style.backgroundColor = "#f59e0b";
  else bar.style.backgroundColor = "#10b981";
}
