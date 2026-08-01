// Shared loading bar. Used on every page — the auth pages had no script at
// all, which is why the bar only appeared on the landing page.
//
// It runs in two halves, which is what makes it feel finished rather than
// stalling: leaving a page fills it to 90%, and arriving on the next page
// snaps it to 100% and fades it out. Previously it stopped at 92% and sat
// there, because nothing ever completed it.

(function () {
  var bar = document.createElement("div");
  bar.className = "route-loading-bar";
  document.body.appendChild(bar);

  // --- Arriving: finish the bar the previous page started ---------------
  requestAnimationFrame(function () {
    bar.classList.add("arriving");
    setTimeout(function () {
      bar.classList.add("done");
      setTimeout(function () {
        bar.classList.remove("arriving", "done", "leaving");
        bar.style.width = "";
      }, 350);
    }, 180);
  });

  // --- Leaving: run it up to 90% and hold ------------------------------
  function startLoading() {
    bar.classList.remove("arriving", "done");
    bar.classList.add("leaving");
  }

  document.querySelectorAll("a[href]").forEach(function (link) {
    var href = link.getAttribute("href");
    if (!href || href.charAt(0) === "#") return; // in-page anchor
    if (link.target === "_blank") return; // opens elsewhere
    if (link.id === "logoutTrigger") return; // opens the modal
    link.addEventListener("click", startLoading);
  });

  document.querySelectorAll("form").forEach(function (form) {
    form.addEventListener("submit", startLoading);
  });

  // Coming back via the browser's Back button restores from cache without
  // firing load, so the bar has to be cleared explicitly.
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) {
      bar.className = "route-loading-bar";
      bar.style.width = "";
    }
  });
})();
