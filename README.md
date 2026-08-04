# 🛡️ Mech Spec LMS
### ITExperience Mid-Year Event 2026 · Cybersecurity Team C

![Project Status](https://img.shields.io/badge/Status-In%20Development-gold?style=for-the-badge)
![Event](https://img.shields.io/badge/Event-ITExperience%202026-blue?style=for-the-badge)
![Team](https://img.shields.io/badge/Team-Cybersecurity%20Team%20C-darkgreen?style=for-the-badge)
![Stack](https://img.shields.io/badge/Stack-HTML%20%7C%20CSS%20%7C%20PHP%20%7C%20MySQL-orange?style=for-the-badge)

---

## 📌 Project Overview

This repository contains the **full frontend and authentication layer** of the **Mech Spec LMS** platform — a secure Learning Management System developed as part of the **ITExperience Mid-Year Event 2026**.

**Mech Spec Technologies** is a technology company committed to building secure, scalable, and innovative digital solutions. The platform enables instructors to publish and monetize courses while offering students an accessible, secure, and engaging learning experience.

> **Scope:** This repo covers the **Frontend UI + PHP Authentication + Role-Based Dashboards**. It is maintained collaboratively by all team members.

---

## 🏆 Event Context

| Field | Details |
|-------|---------|
| **Event** | ITExperience Mid-Year Event 2026 |
| **Client** | Mech Spec Technologies |
| **Project** | Secure Learning Management Platform (LMS) — MVP |
| **Project Start** | Tuesday, 14th July 2026 |
| **Submission Deadline** | 14th August 2026 |
| **Mentor** | @Fidelis |
| **General Support** | @Collins |

---

## 👥 Team — Cybersecurity Team C

| Member | GitHub | Role |
|--------|--------|------|
| **Charles** | `charles-xv` | **Team Lead / Full-Stack Developer** |
| **Ernest** | — | Backend Developer |
| **Oluwa Timilehin** | — | Frontend Developer |
| **Raquib Souley** | — | Security Analyst & QA Engineer · Frontend UI |

---

## 🗂️ Repository Structure

The project follows a strict `PascalCase` naming convention as defined by the Team Lead.

```
IT-experience-project/
│
├── 📄 Index.html                    ← Landing Page (Hero, Courses, Pricing, Testimonials)
├── 📄 Index.css                     ← Global design system (variables, layout, animations)
├── 📄 index.js                      ← Global JavaScript
├── 📄 AuthPage.css                  ← Shared styles for Login & Signup pages
├── 📄 AuthPage.js                   ← Shared JS for Login & Signup pages
│
├── 📄 LoginPage.php                 ← Secure user login page (PHP)
├── 📄 LoginPage.css                 ← Login page styles
├── 📄 SignupPage.php                ← Secure user registration page (PHP)
├── 📄 SignupPage.css                ← Signup page styles
│
├── 📁 DASHBOARD/                    ← Static HTML dashboard prototypes
│   ├── 📄 StudentDashboard.html     ← Student dashboard prototype (Timilehin)
│   └── 📄 InstructorDashboard.html  ← Instructor dashboard prototype (Raquib Souley)
│
├── 📁 dashboards/                   ← PHP dashboards (session-protected, role-based)
│   ├── 📄 StudentDashboard.php      ← Student view (requires login + student role)
│   ├── 📄 InstructorDashboard.php   ← Instructor view (requires login + instructor role)
│   ├── 📄 AdminDashboard.php        ← Admin view (requires login + admin role)
│   ├── 📄 Dashboard.css             ← Dashboard layout & widget styles
│   └── 📄 Dashboard.js              ← Dashboard JavaScript (logout modal, etc.)
│
├── 📁 php/                          ← Backend logic (PHP)
│   ├── 📄 Login.php                 ← Handles login form submission + rate limiting
│   ├── 📄 Signup.php                ← Handles registration + bcrypt hashing
│   ├── 📄 Logout.php                ← Destroys session and redirects
│   ├── 📄 SessionGuard.php          ← Protects pages, enforces roles, idle timeout
│   └── 📄 database.php              ← PDO database connection
│
├── 📁 database/                     ← Database setup
│   └── 📄 schema.sql                ← MySQL schema (Users table + LoginAttempts table)
│
└── 📄 README.md
```

---

## ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🔒 **Security-First Design** | Hero section highlights the platform's Security-First architecture |
| 📊 **Live Progress Panel** | Interactive course progress tracker (initializes at 0% for guests) |
| 🎓 **Course Showcase** | Featured courses with category filters (Security / Dev / AI) |
| 💬 **Testimonials** | Learner reviews with animated cards |
| 💰 **Pricing Toggle** | Monthly / Annual billing switch with discount logic |
| 🤖 **Dual-Mode AI Assistant** | Floating assistant supporting **Local Offline Mode** (fast JS rules) and **Live Gemini AI Mode** (secure PHP backend proxy via `php/ChatbotAPI.php`) |
| 🔐 **Secure Auth (PHP)** | Login & Signup with bcrypt hashing, CSRF-aware, rate limiting |
| 🧱 **Role-Based Dashboards** | Separate views for Student, Instructor, and Admin roles |
| ⏱️ **Session Idle Timeout** | Sessions expire after 30 minutes of inactivity |
| 📱 **Responsive Design** | Fully responsive for mobile, tablet, and desktop |

---

## 🚀 Getting Started (with XAMPP)

This project requires **PHP + MySQL** to run. Follow the steps below.

### Step 1 — Install XAMPP
Download and install [XAMPP](https://www.apachefriends.org/) if you don't have it.

### Step 2 — Clone the repository
```bash
git clone https://github.com/charles-xv/IT-experience-project.git
```
Place the cloned folder inside `C:\xampp\htdocs\` so the final path is:
```
C:\xampp\htdocs\IT-experience-project\
```

### Step 3 — Start XAMPP
Open the **XAMPP Control Panel** and start both **Apache** and **MySQL**.

### Step 4 — Import the database
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) in your browser.
2. Click the **Import** tab → **Choose File**.
3. Navigate to `database/schema.sql` and select it.
4. Click **Go**. A green success message will appear and `itexperience_db` will be created.

### Step 5 — Open the site
```
http://localhost/IT-experience-project/Index.html
```
> ⚠️ Always use `localhost` — **not** Live Server or `127.0.0.1:5500`. Live Server cannot run PHP.

### Step 6 — Sign up and log in
Click **Sign Up**, create an account, then **Log In**. You will be redirected to your dashboard based on your role.

#### View Dashboard Prototypes (no login required)
| Dashboard | URL |
|-----------|-----|
| Student prototype | `http://localhost/IT-experience-project/DASHBOARD/StudentDashboard.html` |
| Instructor prototype | `http://localhost/IT-experience-project/DASHBOARD/InstructorDashboard.html` |

---

## 🎨 Design System

| Token | Value |
|-------|-------|
| Primary Font | `Plus Jakarta Sans` (Google Fonts) |
| Navy 950 | `#070d19` |
| Navy 900 | `#0d1526` |
| Gold Accent | `#f7c331` |
| Cyan Accent | `#06b6d4` |
| Emerald Accent | `#10b981` |
| UI Style | Dark mode, glassmorphism, sidebar layout, micro-animations |

---

## 🔐 Security Practices Applied

As a Security-First platform, the following practices are applied:

- **bcrypt password hashing** — Passwords are never stored in plain text (`password_hash()` / `password_verify()`).
- **Rate limiting** — Login attempts are tracked in `LoginAttempts` table; accounts are locked after repeated failures.
- **Session protection** — `SessionGuard.php` enforces authentication and a 30-minute idle timeout on every protected page.
- **Role-Based Access Control (RBAC)** — Students, Instructors, and Admins are each locked to their own dashboard.
- **No sensitive data exposure** — No hardcoded API keys, credentials, or tokens anywhere in the codebase.
- **`rel="noopener noreferrer"`** — Applied on all external links to prevent tab-napping.
- **HTTPS-only external resources** — Google Fonts and external assets are loaded over HTTPS.
- **No-cache headers on protected pages** — Prevents the browser back button from showing a dashboard after logout.

---

<div align="center">

**Built with ❤️ by Cybersecurity Team C — ITExperience 2026**

*"Security is not a feature, it's a foundation."*

</div>