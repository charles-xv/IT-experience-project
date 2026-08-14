<?php
// php/secrets.template.php
// ============================================================
// TEMPLATE — Copy this file and rename it to: php/secrets.php
// Then replace the value below with your real Gemini API key.
//
// php/secrets.php is listed in .gitignore and will NEVER be
// committed to the repository — your key stays private.
// ============================================================

define('GEMINI_API_KEY', 'PASTE_YOUR_API_KEY_HERE');


// Authentication/email settings
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_WEB_CLIENT_ID.apps.googleusercontent.com');
define('APP_URL', 'http://localhost/IT-experience-project');
define('MAIL_FROM_EMAIL', 'yourgmail@gmail.com');
define('MAIL_FROM_NAME', 'Mech Spec LMS');

// Gmail SMTP. Use a Google App Password, not your normal Gmail password.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', '587');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'yourgmail@gmail.com');
define('SMTP_PASSWORD', 'YOUR_16_CHARACTER_APP_PASSWORD');
