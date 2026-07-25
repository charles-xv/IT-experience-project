# 🛡️ Mech Spec LMS — Landing Page
### ITExperience Mid-Year Event 2026 · Cybersecurity Team C

![Project Status](https://img.shields.io/badge/Status-In%20Development-gold?style=for-the-badge)
![Event](https://img.shields.io/badge/Event-ITExperience%202026-blue?style=for-the-badge)
![Team](https://img.shields.io/badge/Team-Cybersecurity%20Team%20C-darkgreen?style=for-the-badge)
![Stack](https://img.shields.io/badge/Stack-HTML%20%7C%20CSS%20%7C%20JS-orange?style=for-the-badge)

---

## 📌 Project Overview

This repository contains the **public-facing frontend** of the **Mech Spec LMS** platform — a secure Learning Management System developed as part of the **ITExperience Mid-Year Event 2026**.

**Mech Spec Technologies** is a technology company committed to building secure, scalable, and innovative digital solutions. The platform enables instructors to publish and monetize courses while offering students an accessible, secure, and engaging learning experience.

> **Scope of this repository:** This repo covers the **Frontend UI** according to the team's naming conventions and structure. It is maintained collaboratively by the team members.

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

| Member | Role |
|--------|------|
| **Charles (charles-xv)** | **Team Lead / Full-Stack Developer** |
| Ernest | Backend Developer |
| **Raquib Souley** | **Security Analyst & QA Engineer** · Frontend Security & UI |

---

## 🗂️ Repository Structure

The project follows a strict naming convention (`PascalCase` for HTML and CSS files) as defined by the Team Lead to ensure consistency for the judges.

```
/ (Root)
├── Index.html          ← Main Landing Page (Hero, Courses, Testimonials, Pricing)
├── Index.css           ← Styles for the Landing Page
├── LoginPage.html      ← Secure User Login Page
├── LoginPage.css       ← Styles for the Login Page
├── SignupPage.html     ← Secure User Registration Page
└── SignupPage.css      ← Styles for the Registration Page
```

---

## ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🔒 **Security Badge** | Hero section highlights the Security-First architecture |
| 📊 **Live Progress Panel** | Interactive course progress tracker with play/pause |
| 🎓 **Course Showcase** | Featured courses with category filters (Security / Dev / AI) |
| 💬 **Testimonials** | Learner reviews with animated cards |
| 💰 **Pricing Toggle** | Monthly / Annual billing switch with discount logic |
| 🤖 **AI Support Widget** | Floating chatbot interface for platform FAQ |
| 🔐 **Secure Auth UI** | Dedicated Login and Signup pages designed for secure data entry |
| 📱 **Responsive Design** | Fully responsive for mobile, tablet, and desktop |

---

## 🚀 Getting Started

No build tools or servers are required to view the frontend. It is built with vanilla web technologies.

1. Clone the repository:
   ```bash
   git clone https://github.com/charles-xv/IT-experience-project.git
   ```
2. Open the project folder.
3. Double-click `Index.html` to open it in your preferred web browser.

---

## 🎨 Design System

| Token | Value |
|-------|-------|
| Primary Font | `Plus Jakarta Sans` (Google Fonts) |
| Navy 950 | `#070d19` |
| Gold Accent | `#f7c331` |
| Cyan Accent | `#06b6d4` |
| Emerald Accent | `#10b981` |
| UI Style | Dark mode, glassmorphism, micro-animations |

---

## 🔐 Security Practices Applied

As a Security-First platform, the following UI/UX security practices are applied:
- **No sensitive data exposure** — No hardcoded API keys, credentials, or tokens.
- **`rel="noopener noreferrer"`** on all external links to prevent tab-napping.
- **Accessible Inputs** — Clear focus states (`:focus-visible`) for keyboard navigation.
- **HTTPS-only external resources** (Google Fonts, external imagery).

*(Additional backend security like bcrypt hashing, RBAC, and input sanitization will be handled in the backend repository).*

---

<div align="center">

**Built with ❤️ by Cybersecurity Team C — ITExperience 2026**

*"Security is not a feature, it's a foundation."*

</div>