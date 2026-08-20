# Secure Learning Management System (MVP)

A secure web-based Learning Management System (LMS) developed by **Cybersecurity Team C** for the **ITExperience Mid-Year Event 2026**.

The project is designed to provide a complete e-learning workflow for **Students, Instructors, and Administrators**, while applying practical cybersecurity principles throughout the application's authentication, authorization, session management, database operations, and monitoring processes.

---

## Project Overview

The **Secure Learning Management System (MVP)** is a web-based e-learning platform that allows students to browse, purchase, and enroll in courses, while instructors can create and publish course content after receiving administrator approval.

The platform uses a **single authentication entry point**. During login and registration, the application determines the user's role through the system's database and route-based authorization logic, then directs the user to the dashboard permitted for that role.

The system was developed with a **secure-by-design approach**, rather than treating security as an afterthought. Access privileges are controlled through Role-Based Access Control (RBAC), instructor accounts require administrator approval before publishing privileges are granted, passwords are securely hashed, login attempts are monitored, and security events are recorded.

Because the project is an MVP and does not maintain large-scale media storage for course videos, instructors provide **YouTube video links**, which are integrated and displayed within the LMS. This allows course video content to be delivered without requiring large video files to be stored directly in the application's database.

---

## Project Objectives

The main objectives of the project are to:

* Develop a functional web-based Learning Management System.
* Support three distinct user roles: Student, Instructor, and Administrator.
* Implement secure authentication and Role-Based Access Control.
* Prevent unauthorized instructor activity through administrator approval.
* Provide students with a simulated course purchasing and enrollment workflow.
* Provide instructors with a platform for creating and publishing courses.
* Integrate YouTube-hosted video content into courses.
* Implement security controls against common web application threats.
* Provide administrators with monitoring and management capabilities.
* Demonstrate practical application of secure web development principles.

---

## Key Features

### Student Features

* User registration and login.
* Role-based access to the student dashboard.
* Browse available courses.
* View course information and YouTube-based video content.
* Simulated course purchasing.
* Course enrollment and access to enrolled courses.
* Secure session management.

### Instructor Features

* Instructor registration through the same authentication system.
* Instructor accounts placed in a pending state until administrator approval.
* Administrator-controlled activation of instructor privileges.
* Course creation and publishing.
* Addition of YouTube-hosted course videos through video links.
* Access to the instructor dashboard only after authorization.

### Administrator Features

* Administrator authentication and dashboard access.
* Instructor approval and rejection.
* User and platform management.
* Security monitoring and event logging.
* Control over privileged administrative functions.

---

## Cybersecurity Features

Security was a major component of the project. The following controls were implemented:

### Role-Based Access Control (RBAC)

The system separates privileges according to three user roles:

* Student
* Instructor
* Administrator

Users are routed to dashboards and functions according to their authorized role. This prevents users from accessing functionality that is outside their permitted privileges.

### Instructor Approval Workflow

Instructor registration does not immediately grant instructor privileges. New instructor accounts remain pending until an administrator reviews and approves them.

This helps prevent unauthorized users from gaining immediate access to privileged course-management functionality.

### Password Hashing

User passwords are securely hashed using **Bcrypt** rather than being stored as plaintext passwords.

### Password Strength Validation

The registration system includes a dynamic password-strength meter to encourage users to create stronger passwords.

### Account Lockout

The authentication system monitors failed login attempts and automatically locks an account after **three consecutive failed attempts**, helping reduce the risk of brute-force password attacks.

### SQL Injection Protection

Database operations are implemented using **PHP PDO and prepared statements**, together with input validation, to reduce the risk of SQL injection attacks.

### Session Management

The application uses session-management logic to maintain authenticated users and control access to protected areas of the application.

### Security Event Logging

Important security-related activities are recorded through the application's security monitoring and logging mechanisms.

### Google Authentication

The system supports Google-based authentication through **Google OAuth 2.0**.

---

## Course Video Integration

The system does not store large video files directly in the application database.

Instead, instructors can provide a **YouTube video link** when creating course content. The LMS processes and embeds the linked YouTube video so that students can view the content directly through the platform.

This approach reduces the need for large-scale video storage while keeping course video content accessible through the LMS.

---

## Simulated Purchasing and Enrollment

The LMS includes a **simulated checkout and purchasing workflow**.

Students can select a course, proceed through the simulated purchase process, and become enrolled in the selected course.

The payment process is intentionally a simulation for the MVP and does not process real financial transactions.

---

## Technology Stack

### Frontend

* HTML5
* CSS3
* JavaScript

### Backend

* PHP
* MySQL
* PHP PDO
* XAMPP

### Authentication & Security

* Bcrypt password hashing
* Google OAuth 2.0
* Role-Based Access Control (RBAC)
* Session management
* Login attempt monitoring
* Three-strike account lockout
* Password strength validation
* Input validation
* Prepared statements
* Security event logging

### AI & Platform Integrations

* Gemini AI API for the platform assistant
* Google OAuth 2.0 for Google authentication
* YouTube video integration/embedding for course content

### Development & Version Control

* Visual Studio Code
* Git
* GitHub
* XAMPP

---

## System Architecture

The application follows a client-server web architecture.

**Frontend → PHP Application Logic → MySQL Database**

The authentication workflow uses a centralized login/registration entry point. After authentication, the application determines the user's assigned role and routes the user to the appropriate dashboard.

The main authorization structure is:

```text
                    ┌─────────────────────┐
                    │   Authentication    │
                    │ Login / Registration│
                    └──────────┬──────────┘
                               │
                         Role Determination
                               │
             ┌─────────────────┼─────────────────┐
             │                 │                 │
             ▼                 ▼                 ▼
        ┌─────────┐      ┌────────────┐    ┌────────────┐
        │ Student │      │ Instructor │    │   Admin    │
        └────┬────┘      └──────┬─────┘    └─────┬──────┘
             │                  │                 │
             ▼                  ▼                 ▼
      Student Dashboard   Pending/Approved   Admin Dashboard
                              Workflow
```

Instructor privileges are controlled separately from ordinary user authentication. A registered instructor must receive administrator approval before gaining publishing privileges.

---

## Database

The system uses **MySQL** as its relational database.

The database manages information including:

* User accounts
* User roles
* Instructor approval status
* Courses
* Course enrollment
* Simulated purchase information
* Authentication/session-related data
* Security events

Database access is implemented through **PHP PDO and prepared statements**.

---

## Challenges & Solutions

### Challenge 1: Secure Role-Based Access Control

**Challenge:** Enforcing Role-Based Access Control securely during the onboarding process without compromising the user experience.

**Solution:** Implemented a role-based registration and authentication workflow where users select their roles during sign-up. Student accounts receive normal student access, while instructor accounts remain pending until an Administrator reviews and approves them. Unauthorized users are prevented from accessing privileged dashboards and course-publishing functions.

### Challenge 2: Protection Against Brute-Force Login Attacks

**Challenge:** Protecting the application against repeated failed login attempts while maintaining accessibility for legitimate users.

**Solution:** Engineered a custom session-management and login-attempt mechanism that monitors failed login attempts and automatically locks an account after three consecutive failures, strengthening the authentication gateway against brute-force attacks.

### Challenge 3: Collaborative Development and UI Integration

**Challenge:** Managing concurrent code updates and UI integration across different team members, especially after team responsibilities changed during development.

**Solution:** Maintained continuous communication within the team, reassigned frontend dashboard tasks where necessary, and performed manual testing to ensure that frontend components correctly connected with the backend database and security logic.

### Challenge 4: Limited Media Storage for Course Content

**Challenge:** The MVP does not provide large-scale storage for hosting instructor course videos directly within the application's database. Storing large video files locally would significantly increase storage requirements and database/server overhead.

**Solution:** Integrated YouTube video content into the LMS by allowing instructors to provide YouTube-hosted video links. The videos are then embedded within the course interface, allowing students to access course video content through the LMS without requiring large video files to be stored directly in the application's database.

---

## Project Outcomes

The project successfully produced a functional **Secure Learning Management System (MVP)** supporting Student, Instructor, and Administrator roles.

The completed system provides:

* Centralized authentication and role-based routing.
* Secure instructor approval workflow.
* Student course browsing and enrollment.
* Simulated course purchasing.
* Instructor course creation and publishing.
* YouTube-based course video integration.
* Bcrypt password hashing.
* Password-strength validation.
* Three-strike account lockout.
* SQL injection protection through PDO prepared statements.
* Security event monitoring and logging.
* Google authentication.
* Gemini AI-powered platform assistance.
* Administrator management and security controls.
* Online deployment of the completed MVP.

The project provided practical experience in **backend development, database management, authentication, authorization, secure coding, deployment, AI integration, and collaborative software development**.

---

## Live Project

**Website:**
https://mechspec.com.ng

The application uses a centralized authentication entry point and routes authenticated users to their authorized dashboards according to their assigned role.

---

## GitHub Repository

**Repository:**
https://github.com/charles-xv/IT-experience-project

---

## Demo & Presentation

**Demo / Showcase:**
[(https://drive.google.com/drive/folders/1zNwU5-EwF6qrgWD5zwVDuWYMMTEqYUBq?usp=sharing)]


| **Charles Nnanna**  | **Team Lead / Backend Engineer & Security Lead**
 | Led backend development, implemented the application's core backend logic and security architecture, developed authentication and access-control mechanisms, implemented account lockout and password-security features, worked on Google authentication and monitoring functionality, contributed to the simulated checkout and administrator dashboard, handled live deployment, and resolved major frontend/UI integration issues including chatbot UI fixes. |

| **Souley Akewi Raquib** | **Frontend Engineer** 
 | Contributed to frontend development and UI design, built the landing page and instructor dashboard frontend, and implemented the Gemini AI chatbot/assistant for platform navigation.                                                                                                                                                                                                    |
| **Onishola Timilehin**  | **Frontend / UI/UX**
 | Worked on UI/UX design and contributed frontend code for the student dashboard. Some initial dashboard work was later modified or reassigned during development to maintain project progress and integration consistency.                                                                                                                                                                                                                                        |

---

## Development Tools

* Visual Studio Code
* XAMPP
* Git
* GitHub
* MySQL
* PHP

---

## Project Context

**Event:** ITExperience Mid-Year Event 2026
**Team:** Cybersecurity Team C
**Project:** Secure Learning Management System (MVP)

---

## Disclaimer

This project is an educational Minimum Viable Product developed to demonstrate practical web development and cybersecurity concepts.

The purchasing/checkout functionality is simulated and does not process real payments.
