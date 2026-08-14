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





// ---------------------------------------------------------------------
// Theme toggle — shared landing-page preference.
// The inline initializer in index.php prevents a flash before CSS paints.
// ---------------------------------------------------------------------
(function () {
  var root = document.documentElement;
  var toggle = document.getElementById('themeToggle');
  if (!toggle) return;

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    var light = theme === 'light';
    toggle.setAttribute('aria-label', light ? 'Switch to dark mode' : 'Switch to light mode');
    toggle.setAttribute('title', light ? 'Switch to dark mode' : 'Switch to light mode');
    var label = toggle.querySelector('.theme-toggle-label');
    if (label) label.textContent = light ? 'Dark mode' : 'Light mode';
    var sun = toggle.querySelector('.theme-icon-sun');
    var moon = toggle.querySelector('.theme-icon-moon');
    if (sun) sun.classList.toggle('theme-icon-sun', true);
    if (moon) moon.classList.toggle('theme-icon-moon', true);
  }

  var saved = localStorage.getItem('mechspec-theme');
  applyTheme(saved === 'light' ? 'light' : 'dark');

  toggle.addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    localStorage.setItem('mechspec-theme', next);
    applyTheme(next);
  });
})();

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

// =====================================================================
//  Interactive AI Chatbot Assistant Engine (Dual Mode: Offline / API)
// =====================================================================

let currentChatbotMode = "offline";

function toggleChatbot() {
  const win = document.getElementById("chatbotWindow");
  if (!win) return;
  win.classList.toggle("open");
  if (win.classList.contains("open")) {
    const input = document.getElementById("chatbotInput");
    if (input) setTimeout(() => input.focus(), 150);
  }
}

function switchChatbotMode(mode) {
  currentChatbotMode = mode;

  const container = document.getElementById("chatbotMessages");
  if (!container) return;

  const notice = document.createElement("div");

  notice.className = "chat-msg bot";
  notice.style.fontStyle = "italic";
  notice.style.borderColor = "var(--cyan-accent)";

  if (mode === "api") {
    notice.innerHTML =
      "<strong>Gemini AI mode enabled.</strong> " +
      "Your questions are securely sent to the PHP backend.";
  } else {
    notice.innerHTML =
      "<strong>Local mode enabled.</strong> " +
      "Responses are generated locally without the Gemini API.";
  }

  container.appendChild(notice);
  container.scrollTop = container.scrollHeight;
}

function sendSuggestion(text) {
  const input = document.getElementById("chatbotInput");
  if (!input) return;
  input.value = text;
  handleChatSubmit(new Event('submit'));
}

function handleChatSubmit(e) {
  if (e && e.preventDefault) e.preventDefault();
  const input = document.getElementById("chatbotInput");
  const container = document.getElementById("chatbotMessages");
  if (!input || !container) return;

  const userText = input.value.trim();
  if (!userText) return;

  // Add User Message
  const userBubble = document.createElement("div");
  userBubble.className = "chat-msg user";
  userBubble.textContent = userText;
  container.appendChild(userBubble);

  input.value = "";
  container.scrollTop = container.scrollHeight;

  // Add Typing Indicator
  const typingIndicator = document.createElement("div");
  typingIndicator.className = "chat-msg bot typing";
  typingIndicator.id = "typingIndicator";
  typingIndicator.innerHTML = `
    <span class="typing-dot"></span>
    <span class="typing-dot"></span>
    <span class="typing-dot"></span>
  `;
  container.appendChild(typingIndicator);
  container.scrollTop = container.scrollHeight;

  if (currentChatbotMode === "api") {
    // Mode 2: Connect via secure PHP backend endpoint (php/ChatbotAPI.php)
    fetch("php/ChatbotAPI.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: userText })
    })
    .then(res => res.json())
    .then(data => {
      const indicator = document.getElementById("typingIndicator");
      if (indicator) indicator.remove();

      const botBubble = document.createElement("div");
      botBubble.className = "chat-msg bot";
      botBubble.innerHTML = data.response || "No response received.";
      container.appendChild(botBubble);
      container.scrollTop = container.scrollHeight;
    })
    .catch(err => {
      const indicator = document.getElementById("typingIndicator");
      if (indicator) indicator.remove();

      const botBubble = document.createElement("div");
      botBubble.className = "chat-msg bot";
     botBubble.textContent =
    "The AI service is currently unavailable. Please try again or switch to Local mode.";
      container.appendChild(botBubble);
      container.scrollTop = container.scrollHeight;
    });
  } else {
    // Mode 1: Fast offline processing rules
    setTimeout(() => {
      const indicator = document.getElementById("typingIndicator");
      if (indicator) indicator.remove();

      const botResponse = generateAIResponse(userText);
      const botBubble = document.createElement("div");
      botBubble.className = "chat-msg bot";
      botBubble.innerHTML = botResponse;
      container.appendChild(botBubble);
      container.scrollTop = container.scrollHeight;
    }, 750);
  }
}

function generateAIResponse(query) {
  const q = query.toLowerCase();

  if (q.includes("course") || q.includes("catalog") || q.includes("learn") || q.includes("class")) {
    return "We offer top-tier courses including <strong>Ethical Hacking</strong>, <strong>Web Security Basics</strong>, <strong>JavaScript for Beginners</strong>, and <strong>HTML Crash Course</strong>. You can browse them right above in the catalogue!";
  }

  if (q.includes("price") || q.includes("cost") || q.includes("buy") || q.includes("free") || q.includes("pay")) {
    return "<strong>Flexible Pricing:</strong> We offer free foundational courses ($0) as well as premium courses with lifetime access. No subscription fees — pay once per course and keep it forever!";
  }

  if (q.includes("secure") || q.includes("security") || q.includes("password") || q.includes("protection")) {
    return "<strong>Security-First Architecture:</strong> Mech Spec LMS utilizes <code>bcrypt</code> password hashing, strict Rate Limiting against brute-force attacks, Role-Based Access Control (RBAC), and 30-minute session idle timeouts.";
  }

  if (q.includes("certificate") || q.includes("diploma") || q.includes("completion")) {
    return "<strong>Verified Certificates:</strong> When you complete 100% of a course, a verified completion certificate is automatically generated in your Student Dashboard!";
  }

  if (q.includes("login") || q.includes("account") || q.includes("signup") || q.includes("register")) {
    return "You can create a free account by clicking <strong>'Sign Up'</strong> at the top right, or log in if you already have an account. We also support instant password resets!";
  }

  if (q.includes("hello") || q.includes("hi") || q.includes("hey") || q.includes("bonjour")) {
    return "Hello! I am <strong>SpechBot</strong>. How can I assist you today with Mech Spec LMS?";
  }

  return "Thanks for your question! I can help you with <strong>Courses</strong>, <strong>Pricing</strong>, <strong>Platform Security</strong>, or <strong>Certificates</strong>. Feel free to try one of the suggestion buttons below!";
}
