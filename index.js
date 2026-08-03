// Course filter on the landing page. The buttons carry data-filter and the
// cards carry data-cat, both generated from the real category names, so this
// works whatever categories the instructors actually create.
document.querySelectorAll('.filter-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var target = btn.dataset.filter;

    document.querySelectorAll('.filter-btn').forEach(function (b) {
      b.classList.remove('active');
    });
    btn.classList.add('active');

    document.querySelectorAll('.course-card').forEach(function (card) {
      var show = target === 'all' || card.dataset.cat === target;
      card.classList.toggle('is-hidden', !show);
    });
  });
});

// Navbar active link — moves the gold underline to the section you're viewing
// (or the one you click), instead of being stuck on Home.
const navLinks = document.querySelectorAll(".nav a");

// While a click-scroll is animating, the scroll handler is paused so it can't
// fight the click and snap the underline back.
let clickLock = false;
let clickLockTimer = null;

navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    navLinks.forEach((l) => l.classList.remove("current"));
    link.classList.add("current");
    clickLock = true;
    clearTimeout(clickLockTimer);
    clickLockTimer = setTimeout(() => {
      clickLock = false;
    }, 800);
  });
});

// Update on scroll: highlight whichever section currently sits under the top
// of the viewport. Only sections that have a matching nav link are tracked, so
// the underline always lands on a real link.
const navHrefs = Array.from(navLinks).map((l) => l.getAttribute("href"));
const sections = navHrefs
  .filter((h) => h && h.startsWith("#") && h.length > 1)
  .map((h) => document.getElementById(h.slice(1)))
  .filter(Boolean);

function updateActiveLink() {
  if (clickLock) return;
  const marker = window.scrollY + 150;
  let currentHref = "#";
  for (const sec of sections) {
    if (marker >= sec.offsetTop) currentHref = "#" + sec.id;
  }
  // Near the very bottom, force the last section (short final sections never
  // reach the marker otherwise).
  if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 4) {
    currentHref = "#" + sections[sections.length - 1].id;
  }
  navLinks.forEach((l) => {
    l.classList.toggle("current", l.getAttribute("href") === currentHref);
  });
}

window.addEventListener("scroll", updateActiveLink);
window.addEventListener("load", updateActiveLink);

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

// =====================================================================
//  Interactivity — the landing page had none of this; Dashboard.js only
//  loads inside the dashboards.
// =====================================================================

// --- Reveal sections as they scroll into view ------------------------
// Cheaper and smoother than listening to every scroll event.
if ('IntersectionObserver' in window) {
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.course-card, .feature, .plan, .testimonial, .heading')
    .forEach(function (el, i) {
      el.classList.add('on-scroll');
      el.style.transitionDelay = ((i % 3) * 70) + 'ms';
      io.observe(el);
    });
}

// --- Header shrinks once you scroll ----------------------------------
var masthead = document.querySelector('.masthead');
if (masthead) {
  window.addEventListener('scroll', function () {
    masthead.classList.toggle('scrolled', window.scrollY > 40);
  });
}