// Certificate download. Opens a print window containing only the chosen
// certificate — the browser's "Save as PDF" then produces the file.
// This keeps it to plain JavaScript with no PDF library to install.
document.querySelectorAll(".cert-download").forEach(function (btn) {
  btn.addEventListener("click", function () {
    var card = document.getElementById(btn.dataset.cert);
    if (!card) return;

    // Clone so the download button itself isn't printed.
    var clone = card.cloneNode(true);
    var actions = clone.querySelector(".cert-actions");
    if (actions) actions.remove();

    var win = window.open("", "_blank", "width=900,height=650");
    if (!win) {
      alert("Please allow pop-ups for this site to download your certificate.");
      return;
    }

    win.document.write(
      '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
        "<title>Certificate</title>" +
        '<link rel="stylesheet" href="../Index.css">' +
        '<link rel="stylesheet" href="Dashboard.css">' +
        "<style>" +
        "body{background:#fff;display:flex;align-items:center;justify-content:center;" +
        "min-height:100vh;margin:0;padding:40px;}" +
        ".cert-card{max-width:640px;width:100%;background:#0b1728;}" +
        "@media print{body{padding:0;} @page{size:landscape;margin:12mm;}}" +
        "</style></head><body>" +
        clone.outerHTML +
        "</body></html>",
    );
    win.document.close();

    // Give the stylesheets a moment to load before the print dialog opens.
    win.onload = function () {
      setTimeout(function () {
        win.print();
      }, 400);
    };
  });
});
