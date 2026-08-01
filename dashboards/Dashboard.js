// Shared dashboard behaviour: the logout confirmation and the progress bars.

// ---------------------------------------------------------------------
//  Progress bars
//  Each fill carries its percentage in data-progress rather than a style
//  attribute, so no per-record inline styling ends up in the markup.
// ---------------------------------------------------------------------
document.querySelectorAll('.course-progress-fill').forEach(function (bar) {
  var pct = parseInt(bar.dataset.progress, 10) || 0;
  bar.style.width = Math.min(Math.max(pct, 0), 100) + '%';
});

// ---------------------------------------------------------------------
//  Logout confirmation
//  Intercepts the log-out link so an accidental click can't end the session.
// ---------------------------------------------------------------------
var logoutTrigger = document.getElementById('logoutTrigger');
var logoutModal   = document.getElementById('logoutModal');
var logoutCancel  = document.getElementById('logoutCancel');
var logoutConfirm = document.getElementById('logoutConfirm');

if (logoutTrigger && logoutModal) {
  logoutTrigger.addEventListener('click', function (e) {
    e.preventDefault();
    logoutModal.classList.add('open');
  });

  logoutCancel.addEventListener('click', function () {
    logoutModal.classList.remove('open');
  });

  // Clicking the dimmed backdrop (but not the card) also cancels.
  logoutModal.addEventListener('click', function (e) {
    if (e.target === logoutModal) logoutModal.classList.remove('open');
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') logoutModal.classList.remove('open');
  });

  logoutConfirm.addEventListener('click', function () {
    window.location.href = '../php/Logout.php';
  });
}


// ---------------------------------------------------------------------
//  Destructive row actions (admin Delete) ask for confirmation first.
//  The message lives in data-confirm on the button.
// ---------------------------------------------------------------------
document.querySelectorAll('[data-confirm]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    if (!window.confirm(btn.dataset.confirm)) {
      e.preventDefault();
    }
  });
});