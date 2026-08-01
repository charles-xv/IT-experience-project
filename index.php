<?php
// Public landing page. No login required, so no SessionGuard here — but the
// session is started anyway so a logged-in visitor can be greeted, and their
// visit attributed to them in the admin's visitor log.
session_start();
require_once __DIR__ . '/php/Database.php';
require_once __DIR__ . '/php/Helpers.php';

// Anonymous visits are logged too — user_id simply stays null. This is what
// fills the Visitor IPs table in the admin dashboard.
log_page_visit($pdo, 'Landing');

// Published courses only. Drafts stay with their instructor until released.
$featured = $pdo->query(
    'SELECT c.course_id, c.title, c.description, c.category, c.thumbnail_url,
            u.full_name AS instructor_name,
            (SELECT COUNT(*) FROM Enrollments e WHERE e.course_id = c.course_id) AS student_count
     FROM Courses c
     JOIN Users u ON u.user_id = c.instructor_id
     WHERE c.status = "published"
     ORDER BY c.created_at DESC
     LIMIT 6'
)->fetchAll();

// Filter buttons are built from the categories that actually exist, so no
// button ever leads to an empty result.
$categories = [];
foreach ($featured as $c) {
    if ($c['category'] && !in_array($c['category'], $categories, true)) {
        $categories[] = $c['category'];
    }
}

// Turns "Web Security" into "web-security" for the filter data attribute.
function slug(string $text): string {
    return strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', trim($text)));
}

$isLoggedIn = !empty($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? '';
$dashboards = [
    'student'    => 'dashboards/StudentDashboard.php',
    'instructor' => 'dashboards/InstructorDashboard.php',
    'admin'      => 'dashboards/AdminDashboard.php',
];
$myDashboard = $dashboards[$role] ?? 'LoginPage.php';
$totalStudents = (int) $pdo->query('SELECT COUNT(*) AS c FROM Users WHERE role = "student"')->fetch()['c'];
$totalCourses  = count($featured);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mech Spec LMS — Learning & Web Security Platform</title>
    <meta
      name="description"
      content="Discover Mech Spec LMS, the secure learning management platform built by Mech Spec Technologies. Master Web Security, AI, and Software Engineering."
    />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700;800&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="LoadingBar.css" />
    <link rel="stylesheet" href="Index.css" />
  </head>
  <body>
    <!-- Masthead / Navbar -->
    <header class="masthead">
      <div class="wrap">
        <a class="brand" href="#">
          <span class="brand-mark">M</span>
          <span>
            <span class="brand-name">Mech Spec LMS</span>
            <span class="brand-sub">Security by Design</span>
          </span>
        </a>

        <nav class="nav">
          <a href="#" class="current">Home</a>
          <a href="#about">About</a>
          <a href="#courses">Courses</a>
          <a href="#pricing">Pricing</a>
          <a href="#testimonials">Testimonials</a>
        </nav>

        <div class="actions">
          <?php if ($isLoggedIn): ?>
          <a class="btn btn-ghost" href="<?= e($myDashboard) ?>">My dashboard</a>
          <a class="btn btn-gold" href="php/Logout.php">Log out</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="LoginPage.php">Log in</a>
          <a class="btn btn-gold" href="SignupPage.php">Sign up</a>
        <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
      <div class="wrap">
        <div>
          <div class="badge-security">
            <span>🔒</span> Role-based access · Encrypted credentials
          </div>

          <h1>Real projects,<br /><em>Real proof.</em></h1>

          <p class="lede">
            Web development, security and AI taught through real projects, not
            slides. Work at your own pace and finish with something you can
            actually show.
          </p>

          <div class="hero-buttons">
            <?php if ($isLoggedIn): ?>
              <a href="<?= e($myDashboard) ?>" class="btn btn-gold">Go to my dashboard →</a>
              <a href="#courses" class="btn btn-ghost">Browse courses</a>
            <?php else: ?>
              <a href="SignupPage.php" class="btn btn-gold">Start learning free →</a>
              <a href="#courses" class="btn btn-ghost">See the courses</a>
            <?php endif; ?>
          </div>

          <!-- Real figures, not claims. Only shown once there is something
               to show, so an empty platform doesn't advertise zeros. -->
          <?php if ($totalCourses > 0 || $totalStudents > 0): ?>
            <div class="hero-stats">
              <?php if ($totalCourses > 0): ?>
                <div class="hero-stat">
                  <strong><?= $totalCourses ?></strong>
                  <span>course<?= $totalCourses === 1 ? '' : 's' ?> live</span>
                </div>
              <?php endif; ?>
              <?php if ($totalStudents > 0): ?>
                <div class="hero-stat">
                  <strong><?= $totalStudents ?></strong>
                  <span>learner<?= $totalStudents === 1 ? '' : 's' ?> enrolled</span>
                </div>
              <?php endif; ?>
              <div class="hero-stat">
                <strong>Free</strong>
                <span>to join</span>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Floating shapes composition. Decorative, so the shapes are hidden
             from screen readers; the badges are real text and stay readable. -->
        <div class="hero-visual">
          <span class="shape shape-square" aria-hidden="true"></span>
          <span class="shape shape-circle-pink" aria-hidden="true"></span>
          <span class="shape shape-ring" aria-hidden="true"></span>
          <span class="shape dot dot-1" aria-hidden="true"></span>
          <span class="shape dot dot-2" aria-hidden="true"></span>
          <span class="shape dot dot-3" aria-hidden="true"></span>
          <span class="shape shape-pill" aria-hidden="true"></span>

          <div class="float-badge badge-top">
            <span class="badge-ico">🛡</span> Security-first
          </div>
          <div class="float-badge badge-mid">
            <span class="badge-ico">&lt;/&gt;</span> Project-based
          </div>
          <div class="float-badge badge-low">
            <span class="badge-ico">🏅</span> Earn certificates
          </div>
        </div>
      </div>
    </section>

    <!-- Feature Highlights -->
    <section class="section about-section" id="about">
      <div class="wrap">

        <!-- Mission — left aligned in its own column, not centred -->
        <div class="about-mission">
          <span class="about-eyebrow">Our mission</span>
          <h2>To make practical tech education accessible, structured and worth finishing.</h2>
          <p>
            Too many online courses are started and never completed. Mech Spec LMS
            works the other way round: short practical lessons, a clear order to
            follow, and visible progress so what you learn turns into something
            you can point to.
          </p>
        </div>

        <div class="about-stand">
          <h3>What we stand for</h3>
          <p>The ideas behind every course on the platform.</p>
        </div>

        <div class="about-points">
          <div class="about-point">
            <span class="about-ico">&lt;/&gt;</span>
            <h4>Learn by building</h4>
            <p>Every course is project-based. You finish with something you made, not just a watch history.</p>
          </div>
          <div class="about-point">
            <span class="about-ico">🔒</span>
            <h4>Secure by design</h4>
            <p>Role-based access, encrypted passwords and login limiting — decided on the server, not the page.</p>
          </div>
          <div class="about-point">
            <span class="about-ico">🏅</span>
            <h4>Proof at the end</h4>
            <p>Complete a course and your certificate is issued automatically, with its own reference number.</p>
          </div>
        </div>

      </div>
    </section>

    <!-- Course Showcase Section -->
    <section class="section" id="courses">
      <div class="wrap">
        <div class="heading">
          <h2>Featured Courses</h2>
          <p>
            Explore top-rated courses across software development, AI, and
            cybersecurity.
          </p>
        </div>

<?php if (!empty($categories)): ?>
          <div class="course-filter">
            <button class="filter-btn active" data-filter="all">All Courses</button>
            <?php foreach ($categories as $cat): ?>
              <button class="filter-btn" data-filter="<?= e(slug($cat)) ?>"><?= e($cat) ?></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (empty($featured)): ?>
          <div class="landing-empty">
            <span class="landing-empty-icon">🎓</span>
            <h3>Courses are on the way</h3>
            <p>Our instructors are putting the first courses together. Create an account and you'll be ready the moment they go live.</p>
            <a href="SignupPage.php" class="btn btn-gold">Create a free account</a>
          </div>
        <?php else: ?>
          <div class="course-grid">
            <?php foreach ($featured as $c): ?>
              <a class="course-card"
                 data-cat="<?= e($c['category'] ? slug($c['category']) : 'all') ?>"
                 href="<?= $isLoggedIn ? 'dashboards/BrowseCourses.php' : 'SignupPage.php' ?>">
                <div class="card-img">
                  <?php if (!empty($c['thumbnail_url'])): ?>
                    <img class="card-thumb" src="<?= e($c['thumbnail_url']) ?>" alt="<?= e($c['title']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="card-thumb-placeholder">🎓</div>
                  <?php endif; ?>
                  <?php if ($c['category']): ?>
                    <span class="card-tag"><?= e($c['category']) ?></span>
                  <?php endif; ?>
                </div>

                <div class="card-body">
                  <h3 class="card-title"><?= e($c['title']) ?></h3>
                  <span class="card-author"><?= e($c['instructor_name']) ?></span>

                  <p class="card-desc"><?= e($c['description']) ?></p>

                  <span class="card-cta">
                    <?= $isLoggedIn ? 'Enrol now' : 'Sign up to enrol' ?> →
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Pricing Section -->
    <section class="section section-alt" id="pricing">
      <div class="wrap">
        <div class="heading">
          <h2>Simple, Transparent Pricing</h2>
          <p>Start free. Upgrade to Pro when you want full access.</p>
        </div>

        <div class="billing-toggle">
          <span>Monthly billing</span>
          <label class="switch">
            <input
              type="checkbox"
              id="billingCheck"
              onchange="toggleBilling()"
              aria-label="Toggle annual billing"
            />
            <span class="slider"></span>
          </label>
          <span>Annual billing <span class="discount-badge">-20%</span></span>
        </div>

        <div class="plans">
          <!-- Free Plan -->
          <div class="plan">
            <div class="plan-name">Free</div>
            <p class="plan-note">Everything you need to get started.</p>
            <div class="price"><b>$0</b><span>/ forever</span></div>
            <ul>
              <li>Access to all free courses</li>
              <li>Preview lessons on paid courses</li>
              <li>Personal progress tracking</li>
              <li>Community support</li>
            </ul>
            <a class="btn btn-outline btn-block" href="SignupPage.php"
              >Create free account</a
            >
          </div>

          <!-- Pro Plan -->
          <div class="plan plan-featured">
            <span class="tag">Most Popular</span>
            <div class="plan-name">Pro LMS</div>
            <p class="plan-note">
              Full access to every course and certificate.
            </p>
            <div class="price">
              <b id="proPrice">$19</b><span id="proPeriod">/ month</span>
            </div>
            <ul>
              <li>Unlimited access to every course</li>
              <li>Verified certificates on completion</li>
              <li>Priority 24/7 AI Support Assistant</li>
              <li>Cancel anytime with no commitments</li>
            </ul>
            <a class="btn btn-gold btn-block" href="SignupPage.php">Go Pro</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section section-alt" id="testimonials">
      <div class="wrap">
        <div class="heading">
          <h2>What Our Learners Say</h2>
          <p>
            Read positive feedback from professionals and students trained by
            Mech Spec Technologies.
          </p>
        </div>

        <div class="testimonials-grid">
          <!-- Testimonial 1 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="testimonial-text">
              "Mech Spec Tech transformed how our engineering team approaches
              web application security. The hands-on OWASP labs and interactive
              security metrics are second to none!"
            </p>
            <div class="testimonial-author">
              <div class="faceless-avatar avatar-cyan">
                <svg
                  class="faceless-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>
              <div class="author-info">
                <h4 class="author-name">Alex R.</h4>
                <span class="author-role"
                  >Cybersecurity Analyst &middot; FinTech Security Lab</span
                >
              </div>
            </div>
          </div>

          <!-- Testimonial 2 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="testimonial-text">
              "The Security-by-Design philosophy is embedded everywhere.
              Learning secure Node.js architecture here gave me the skills to
              safeguard our production APIs against real-world threats."
            </p>
            <div class="testimonial-author">
              <div class="faceless-avatar avatar-gold">
                <svg
                  class="faceless-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>
              <div class="author-info">
                <h4 class="author-name">Sophia M.</h4>
                <span class="author-role"
                  >Full-Stack Developer &middot; CloudScale Systems</span
                >
              </div>
            </div>
          </div>

          <!-- Testimonial 3 -->
          <div class="testimonial-card">
            <div class="stars">★★★★★</div>
            <p class="testimonial-text">
              "Outstanding LMS platform! The AI support assistant answered my
              technical questions instantly, and the progress tracking tools
              keep me engaged every single day."
            </p>
            <div class="testimonial-author">
              <div class="faceless-avatar avatar-emerald">
                <svg
                  class="faceless-icon"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>
              <div class="author-info">
                <h4 class="author-name">David K.</h4>
                <span class="author-role"
                  >DevOps &amp; Cloud Engineer &middot; Nexus Solutions</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call To Action -->
    <section class="cta">
      <div class="wrap">
        <div>
          <h2>Start your learning journey today</h2>
          <p>Create your account in under a minute. No credit card required.</p>
        </div>
        <a class="btn btn-gold" href="SignupPage.php">Get Started For Free →</a>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="wrap">
        <div class="footer-left">
          <div class="footer-brand">
            <span class="brand-mark">M</span>
            <span>Mech Spec LMS</span>
          </div>
          <p class="footer-tagline">
            Empowering future engineers and cybersecurity experts with
            Security-by-Design training.
          </p>
        </div>

        <nav class="footer-links">
          <a href="#courses">Courses</a>
          <a href="#testimonials">Testimonials</a>
          <a href="#pricing">Pricing</a>
          <a href="#about">About</a>
        </nav>

        <div class="footer-socials">
          <span class="social-label">Follow Us</span>
          <div class="social-icons">
            <!-- LinkedIn -->
            <a
              href="https://linkedin.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-link"
              title="LinkedIn"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"
                />
              </svg>
            </a>
            <!-- Facebook -->
            <a
              href="https://facebook.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-link"
              title="Facebook"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"
                />
              </svg>
            </a>
            <!-- Instagram -->
            <a
              href="https://instagram.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-link"
              title="Instagram"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <path
                  d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"
                ></path>
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <span class="footer-legal"
          >&copy; 2026 Mech Spec Technologies. All rights reserved.</span
        >
      </div>
    </footer>

    <!-- Floating AI Assistant Widget -->
    <div class="ai-widget">
      <div class="ai-chat-box" id="aiChatBox">
        <div class="ai-chat-header">
          <span>🤖 Mech Spec Support Assistant</span>
          <span class="ai-close" onclick="toggleAIChat()">✕</span>
        </div>
        <div class="ai-chat-body" id="aiChatBody">
          <div class="ai-msg bot">
            Hello! I am the Mech Spec support assistant. How can I help you
            today? (e.g., "How to register?", "How to purchase a course?")
          </div>
        </div>
        <div class="ai-chat-footer">
          <input
            type="text"
            id="aiInput"
            placeholder="Ask a question..."
            onkeypress="if (event.key === 'Enter') sendAIMessage();"
          />
          <button onclick="sendAIMessage()">Send</button>
        </div>
      </div>
      <div class="ai-btn" onclick="toggleAIChat()" title="AI Support Chat">
        💬
      </div>
    </div>

    <!-- Modal Auth -->
    <div class="modal-overlay" id="modalOverlay">
      <div class="modal-card">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <h3 class="modal-title" id="modalTitle">Sign up</h3>
        <p class="modal-sub" id="modalSub">
          Join the Mech Spec LMS secure platform
        </p>

        <form
          onsubmit="
            event.preventDefault();
            alert('Form submitted successfully (Demo Mode)');
            closeModal();
          "
        >
          <div class="form-group">
            <label for="modalEmail">Email Address</label>
            <input
              type="email"
              id="modalEmail"
              placeholder="your.email@domain.com"
              required
            />
          </div>

          <div class="form-group">
            <label for="passInput">Password</label>
            <input
              type="password"
              id="passInput"
              placeholder="••••••••"
              required
              oninput="checkStrength(this.value)"
            />
            <div class="strength-meter"><span id="strengthBar"></span></div>
          </div>

          <button
            type="submit"
            class="btn btn-gold btn-block"
            id="modalSubmitBtn"
          >
            Create Account
          </button>
        </form>
      </div>
    </div>

    <!-- JavaScript Interactions -->
    <script src="LoadingBar.js"></script>
    <script src="Index.js"></script>
  </body>
</html>