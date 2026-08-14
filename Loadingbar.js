
// Shared route transition indicator for the LMS.
(function () {
  var bar = document.createElement("div");
  bar.className = "route-loading-bar";
  document.body.appendChild(bar);

  var TRANSITION_KEY = "mechSpecRouteLoading";
  var ARRIVED_AT = null; // timestamp when the "arriving" state was shown

  function reset() {
    bar.className = "route-loading-bar";
    bar.style.width = "";
    ARRIVED_AT = null;

    try {
      sessionStorage.removeItem(TRANSITION_KEY);
    } catch (err) {
      // Ignore storage errors.
    }
  }

  function finishLoading() {
    bar.classList.remove("leaving");
    bar.classList.add("arriving");

    // Wait briefly at 92% so the final movement to 100% is visible.
    setTimeout(function () {
      bar.classList.remove("arriving");
      bar.classList.add("done");

      setTimeout(reset, 360);
    }, 120);
  }

  // BUG FIX: `document.readyState === "complete"` was being trusted at face
  // value. On a fast/cached page it can already be true the instant this
  // script runs, which skipped straight to finishLoading() with zero wait —
  // that's why it sometimes finished before the page had visibly rendered.
  // Fix: always treat "arriving" as having a minimum visible duration,
  // measured from the moment we actually show it, regardless of whether
  // readyState happened to already say "complete".
  var MIN_ARRIVING_MS = 250;

  function finishLoadingRespectingMinimum() {
    var elapsed = ARRIVED_AT ? Date.now() - ARRIVED_AT : MIN_ARRIVING_MS;
    var remaining = Math.max(0, MIN_ARRIVING_MS - elapsed);
    setTimeout(finishLoading, remaining);
  }

  function startLoading() {
    bar.classList.remove("arriving", "done");

    // Force a new animation when several links are clicked quickly.
    void bar.offsetWidth;

    bar.classList.add("leaving");

    try {
      sessionStorage.setItem(TRANSITION_KEY, "true");
    } catch (err) {
      // Ignore storage errors.
    }
  }

  // Only show the "arrival" phase when this page was actually
  // reached through a navigation that started the loading bar.
  var shouldFinishTransition = false;

  try {
    shouldFinishTransition =
      sessionStorage.getItem(TRANSITION_KEY) === "true";
  } catch (err) {
    shouldFinishTransition = false;
  }

  if (shouldFinishTransition) {
    // BUG FIX: force a reflow before adding "arriving", same trick already
    // used in startLoading(). Without this, the browser can collapse the
    // 0% -> 92% transition into a single frame and the bar just snaps to
    // 92% instead of visibly growing — which reads as "too fast" even when
    // the underlying timing is correct.
    void bar.offsetWidth;
    bar.classList.add("arriving");
    ARRIVED_AT = Date.now();

    if (document.readyState === "complete") {
      finishLoadingRespectingMinimum();
    } else {
      window.addEventListener(
        "load",
        finishLoadingRespectingMinimum,
        { once: true }
      );
    }
  }

  // BUG FIX (robustness, not the reported symptom): the original code only
  // attached click/submit listeners to elements present at script-load
  // time. Any link or form injected later (course cards rendered by JS,
  // modal content, etc.) silently had no loading bar at all. Delegating
  // from `document` covers those too.
  document.addEventListener("click", function (e) {
    var link = e.target.closest ? e.target.closest("a[href]") : null;
    if (!link) return;

    var href = link.getAttribute("href") || "";

    if (!href || href.charAt(0) === "#" || link.target === "_blank") {
      return;
    }

    if (link.id === "logoutTrigger" || link.hasAttribute("download")) {
      return;
    }

    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
      return;
    }

    startLoading();
  });

  document.addEventListener("submit", function (e) {
    var form = e.target;

    /*
     * AJAX forms such as the AI chatbot prevent the normal
     * browser navigation. They must not trigger the route
     * loading indicator.
     */
    if (e.defaultPrevented) {
      return;
    }

    if (form.id === "chatbotForm") {
      return;
    }

    if (form.dataset && form.dataset.noRouteLoading === "true") {
      return;
    }

    startLoading();
  });

  // If navigation is cancelled/restored from bfcache,
  // don't leave a stuck bar.
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) {
      reset();
    }
  });
})();