/* ================= Admin CMS JavaScript ================= */
(function () {
  "use strict";

  var base = (document.getElementById("baseHref") || {}).value || "/";
  var csrf = (document.getElementById("csrfToken") || {}).value || "";

  /* ---------- Sidebar (mobile) ---------- */
  var sideToggle = document.getElementById("sideToggle");
  var side = document.getElementById("adminSide");
  if (sideToggle && side) {
    sideToggle.addEventListener("click", function () { side.classList.toggle("open"); });
    document.addEventListener("click", function (ev) {
      if (!side.contains(ev.target) && ev.target !== sideToggle && !sideToggle.contains(ev.target)) {
        side.classList.remove("open");
      }
    });
  }

  /* ---------- Confirm modal for destructive actions ---------- */
  var pendingUrl = null;
  var modalEl = document.getElementById("confirmModal");
  var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

  function askConfirm(url, text) {
    pendingUrl = url;
    var t = document.getElementById("confirmText");
    if (t) t.textContent = text || "This action cannot be undone.";
    if (modal) modal.show();
  }

  var confirmYes = document.getElementById("confirmYes");
  if (confirmYes) {
    confirmYes.addEventListener("click", function () {
      if (!pendingUrl) return;
      confirmYes.disabled = true;
      var form = document.createElement("form");
      form.method = "POST";
      form.action = pendingUrl;
      var input = document.createElement("input");
      input.type = "hidden";
      input.name = "csrf";
      input.value = csrf;
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    });
  }

  document.querySelectorAll("[data-confirm-url]").forEach(function (el) {
    el.addEventListener("click", function (ev) {
      ev.preventDefault();
      askConfirm(el.getAttribute("data-confirm-url"), el.getAttribute("data-confirm-text"));
    });
  });

  /* ---------- Toggle switches (status / featured) ---------- */
  document.querySelectorAll("[data-toggle-url]").forEach(function (el) {
    el.addEventListener("change", function () {
      var form = document.createElement("form");
      form.method = "POST";
      form.action = el.getAttribute("data-toggle-url");
      [["csrf", csrf], ["value", el.checked ? "1" : "0"]].forEach(function (pair) {
        var i = document.createElement("input");
        i.type = "hidden"; i.name = pair[0]; i.value = pair[1];
        form.appendChild(i);
      });
      document.body.appendChild(form);
      form.submit();
    });
  });

  /* ---------- Drop zones / file previews ---------- */
  document.querySelectorAll("[data-drop]").forEach(function (zone) {
    var input = zone.querySelector("input[type=file]");
    var preview = zone.parentElement.querySelector(".preview-box");
    var label = zone.querySelector("small");

    zone.addEventListener("click", function () { if (input) input.click(); });
    zone.addEventListener("dragover", function (ev) { ev.preventDefault(); zone.classList.add("drag"); });
    zone.addEventListener("dragleave", function () { zone.classList.remove("drag"); });
    zone.addEventListener("drop", function (ev) {
      ev.preventDefault();
      zone.classList.remove("drag");
      if (input && ev.dataTransfer.files.length) {
        input.files = ev.dataTransfer.files;
        input.dispatchEvent(new Event("change"));
      }
    });
    if (input) {
      input.addEventListener("change", function () {
        if (!input.files.length) return;
        var f = input.files[0];
        if (label) label.textContent = f.name + " (" + Math.round(f.size / 1024) + " KB)";
        if (preview && f.type.indexOf("image/") === 0) {
          preview.style.display = "block";
          preview.querySelector("img").src = URL.createObjectURL(f);
        }
      });
    }
  });

  /* ---------- Auto slug ---------- */
  var titleInput = document.querySelector("[data-slug-source]");
  var slugInput = document.querySelector("[data-slug-target]");
  if (titleInput && slugInput) {
    var touched = slugInput.value !== "";
    slugInput.addEventListener("input", function () { touched = true; });
    titleInput.addEventListener("input", function () {
      if (touched) return;
      slugInput.value = titleInput.value.toLowerCase().trim()
        .replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
    });
  }

  /* ---------- Rich text editor ---------- */
  var rte = document.querySelector("[data-rte]");
  if (rte) {
    var hidden = document.querySelector("[data-rte-value]");
    var toolbar = document.querySelector("[data-rte-toolbar]");

    function sync() { if (hidden) hidden.value = rte.innerHTML; }
    rte.addEventListener("input", sync);
    rte.addEventListener("blur", sync);

    if (toolbar) {
      toolbar.querySelectorAll("button").forEach(function (btn) {
        btn.addEventListener("click", function (ev) {
          ev.preventDefault();
          var cmd = btn.getAttribute("data-cmd");
          var val = btn.getAttribute("data-val") || null;
          rte.focus();
          if (cmd === "createLink") {
            val = prompt("Enter the link URL (https://...)");
            if (!val) return;
          }
          if (cmd === "formatBlock") {
            document.execCommand("formatBlock", false, val);
          } else {
            document.execCommand(cmd, false, val);
          }
          sync();
        });
      });
    }

    // Paste as plain text keeps the markup clean
    rte.addEventListener("paste", function (ev) {
      ev.preventDefault();
      var text = (ev.clipboardData || window.clipboardData).getData("text/plain");
      document.execCommand("insertText", false, text);
      sync();
    });

    if (hidden && !hidden.value) rte.innerHTML = "<p></p>";
    else if (hidden) rte.innerHTML = hidden.value;
    sync();
  }

  /* ---------- Copy to clipboard ---------- */
  document.querySelectorAll("[data-copy]").forEach(function (el) {
    el.addEventListener("click", function () {
      var text = el.getAttribute("data-copy");
      navigator.clipboard.writeText(text).then(function () {
        var old = el.innerHTML;
        el.innerHTML = '<i class="bi bi-check2"></i> Copied';
        setTimeout(function () { el.innerHTML = old; }, 1500);
      });
    });
  });

  /* ---------- Auto-hide alerts ---------- */
  document.querySelectorAll(".a-alert[data-autohide]").forEach(function (el) {
    setTimeout(function () {
      el.style.transition = "opacity .5s, transform .5s";
      el.style.opacity = "0";
      el.style.transform = "translateY(-8px)";
      setTimeout(function () { el.remove(); }, 550);
    }, 5000);
  });
})();
