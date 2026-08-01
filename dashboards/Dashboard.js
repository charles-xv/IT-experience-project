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
//  Destructive actions (delete a course, delete a user) confirm first.
//  The message lives in data-confirm on the button.
//
//  This replaces window.confirm(), which renders the browser's own grey
//  dialog and looks nothing like the rest of the site. The modal below is
//  built once and reused, so no page needs extra markup for it.
// ---------------------------------------------------------------------
(function () {
  var triggers = document.querySelectorAll('[data-confirm]');
  if (!triggers.length) return;

  var overlay = document.createElement('div');
  overlay.className = 'confirm-modal';
  overlay.innerHTML =
    '<div class="confirm-card">' +
      '<span class="confirm-icon">\u26A0</span>' +
      '<h3 id="confirmTitle">Are you sure?</h3>' +
      '<p id="confirmText"></p>' +
      '<div class="confirm-actions">' +
        '<button type="button" class="confirm-cancel">Cancel</button>' +
        '<button type="button" class="confirm-go">Delete</button>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);

  var textEl   = overlay.querySelector('#confirmText');
  var cancelEl = overlay.querySelector('.confirm-cancel');
  var goEl     = overlay.querySelector('.confirm-go');
  var pending  = null;   // the button that was clicked

  function close() {
    overlay.classList.remove('open');
    pending = null;
  }

  triggers.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      pending = btn;
      textEl.textContent = btn.dataset.confirm;
      overlay.classList.add('open');
    });
  });

  cancelEl.addEventListener('click', close);

  // Clicking the dimmed backdrop cancels, clicking the card does not.
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) close();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('open')) close();
  });

  goEl.addEventListener('click', function () {
    if (!pending) return;
    var form = pending.closest('form');
    if (!form) { close(); return; }

    // A submit button's own name/value is only sent when the browser submits
    // the form itself. Calling form.submit() skips that, so the button's
    // name/value is copied into a hidden field first — otherwise the handler
    // would receive no 'action' and reject the request.
    if (pending.name) {
      var hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = pending.name;
      hidden.value = pending.value;
      form.appendChild(hidden);
    }
    close();
    form.submit();
  });
})();

// =====================================================================
//  Interactivity
// =====================================================================

// --- Submit feedback --------------------------------------------------
// Disables the button and swaps the label, which also prevents the
// double-submit that would otherwise create two courses or two enrolments.
document.querySelectorAll('form').forEach(function (form) {
  form.addEventListener('submit', function () {
    var btn = form.querySelector('button[type="submit"], button:not([type])');
    if (btn && !btn.disabled) {
      btn.dataset.originalText = btn.textContent;
      btn.textContent = 'Please wait…';
      btn.classList.add('is-loading');
      // Left enabled for one tick so the button's value still posts, then locked.
      setTimeout(function () { btn.disabled = true; }, 10);
    }
  });
});

// --- Notices fade out on their own ------------------------------------
document.querySelectorAll('.form-notice.success').forEach(function (note) {
  setTimeout(function () {
    note.classList.add('fade-out');
    setTimeout(function () { note.remove(); }, 400);
  }, 5000);
});

// --- Live course search ----------------------------------------------
// Filters the cards already on the page, so there is no round trip.
var courseSearch = document.getElementById('courseSearch');
if (courseSearch) {
  var cards = Array.prototype.slice.call(document.querySelectorAll('.dash-course-card'));
  var emptyMsg = document.getElementById('searchEmpty');

  courseSearch.addEventListener('input', function () {
    var term = courseSearch.value.trim().toLowerCase();
    var shown = 0;

    cards.forEach(function (card) {
      var text = card.textContent.toLowerCase();
      var match = term === '' || text.indexOf(term) !== -1;
      card.classList.toggle('is-hidden', !match);
      if (match) shown++;
    });

    if (emptyMsg) emptyMsg.classList.toggle('is-hidden', shown > 0);
  });
}

// --- Reveal cards on load --------------------------------------------
// A short stagger so a grid of cards doesn't snap in all at once.
document.querySelectorAll('.dash-course-card, .metric-card, .cert-card').forEach(function (el, i) {
  el.style.animationDelay = (i * 45) + 'ms';
  el.classList.add('reveal');
});