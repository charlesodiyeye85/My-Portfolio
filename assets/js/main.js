/* Charles Odeye Damilola — public site interactions */
(function () {
  "use strict";

  /* ---------- Navbar ---------- */
  var navbar = document.querySelector(".navbar-x");
  var toTop = document.querySelector(".to-top");

  function onScroll() {
    var y = window.scrollY || 0;
    if (navbar) navbar.classList.toggle("scrolled", y > 30);
    if (toTop) toTop.classList.toggle("show", y > 500);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  /* ---------- Mobile menu ---------- */
  var burger = document.querySelector(".burger");
  var mobileMenu = document.querySelector(".mobile-menu");
  var mobileClose = document.querySelector(".mobile-close");
  function closeMenu() { if (mobileMenu) mobileMenu.classList.remove("open"); }
  if (burger && mobileMenu) burger.addEventListener("click", function () { mobileMenu.classList.add("open"); });
  if (mobileClose) mobileClose.addEventListener("click", closeMenu);
  if (mobileMenu) mobileMenu.querySelectorAll("a").forEach(function (a) { a.addEventListener("click", closeMenu); });

  /* ---------- Active nav link ---------- */
  var sections = document.querySelectorAll("section[id]");
  var navAnchors = document.querySelectorAll(".nav-links a[href^='#']");
  if (sections.length && navAnchors.length && "IntersectionObserver" in window) {
    var navObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          navAnchors.forEach(function (a) {
            a.classList.toggle("active", a.getAttribute("href") === "#" + entry.target.id);
          });
        }
      });
    }, { rootMargin: "-45% 0px -50% 0px" });
    sections.forEach(function (s) { navObs.observe(s); });
  }

  /* ---------- Typewriter ---------- */
  var typedEl = document.querySelector("[data-typewriter]");
  if (typedEl) {
    var words = (typedEl.getAttribute("data-words") || "").split("|").filter(Boolean);
    var wIdx = 0, cIdx = 0, deleting = false;
    if (words.length) {
      (function tick() {
        var word = words[wIdx];
        cIdx += deleting ? -1 : 1;
        typedEl.textContent = word.slice(0, cIdx);
        var delay = deleting ? 42 : 78;
        if (!deleting && cIdx === word.length) { delay = 1900; deleting = true; }
        else if (deleting && cIdx === 0) { deleting = false; wIdx = (wIdx + 1) % words.length; delay = 350; }
        setTimeout(tick, delay);
      })();
    }
  }

  /* ---------- Reveal on scroll ---------- */
  var reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add("visible"); obs.unobserve(en.target); }
      });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { obs.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add("visible"); });
  }

  /* ---------- Skill bars ---------- */
  var bars = document.querySelectorAll(".skill-bar i[data-level]");
  if (bars.length && "IntersectionObserver" in window) {
    var barObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.style.width = en.target.getAttribute("data-level") + "%";
          barObs.unobserve(en.target);
        }
      });
    }, { threshold: 0.4 });
    bars.forEach(function (b) { barObs.observe(b); });
  } else {
    bars.forEach(function (b) { b.style.width = b.getAttribute("data-level") + "%"; });
  }

  /* ---------- Counters ---------- */
  var counters = document.querySelectorAll("[data-count]");
  if (counters.length && "IntersectionObserver" in window) {
    var cObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        cObs.unobserve(en.target);
        var target = parseInt(en.target.getAttribute("data-count"), 10) || 0;
        var start = performance.now(), dur = 1500;
        (function step(now) {
          var p = Math.min((now - start) / dur, 1);
          en.target.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
          if (p < 1) requestAnimationFrame(step);
        })(start);
      });
    }, { threshold: 0.5 });
    counters.forEach(function (c) { cObs.observe(c); });
  }

  /* ---------- Work filters ---------- */
  var filterBtns = document.querySelectorAll(".filter-btn");
  var workCards = document.querySelectorAll(".work-card");
  filterBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      filterBtns.forEach(function (b) { b.classList.remove("active"); });
      btn.classList.add("active");
      var cat = btn.getAttribute("data-filter");
      workCards.forEach(function (card, i) {
        var match = cat === "all" || card.getAttribute("data-category") === cat;
        card.classList.toggle("hidden", !match);
        if (match) {
          card.style.animation = "none";
          void card.offsetWidth;
          card.style.animation = "fadeUp .5s " + (i % 6) * 0.06 + "s both";
        }
      });
    });
  });

  /* ---------- Lightbox ---------- */
  var lightbox = document.getElementById("workLightbox");
  var visibleWorks = [];

  function currentWorks() {
    return Array.prototype.filter.call(workCards, function (c) {
      return !c.classList.contains("hidden");
    });
  }

  function fillLightbox(card) {
    if (!lightbox || !card) return;
    var img = card.getAttribute("data-image") || "";
    var title = card.getAttribute("data-title") || "";
    var cat = card.getAttribute("data-category") || "";
    var desc = card.getAttribute("data-description") || "";
    var client = card.getAttribute("data-client") || "";
    var tools = card.getAttribute("data-tools") || "";
    var year = card.getAttribute("data-year") || "";
    var link = card.getAttribute("data-link") || "";
    var video = card.getAttribute("data-video") || "";
    var id = card.getAttribute("data-id") || "";

    var media = lightbox.querySelector(".lightbox-media");
    if (media) {
      if (video) {
        var yt = video.match(/(?:youtube\.com\/.*v=|youtu\.be\/)([\w-]{6,})/);
        media.innerHTML = yt
          ? '<iframe width="100%" height="100%" style="min-height:320px" src="https://www.youtube.com/embed/' + yt[1] + '?autoplay=1" title="' + esc(title) + '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>'
          : '<video src="' + esc(video) + '" controls autoplay playsinline></video>';
      } else {
        media.innerHTML = '<img src="' + esc(img) + '" alt="' + esc(title) + '">';
      }
    }
    setText(".lb-cat", categoryFromKey(cat));
    setText(".lb-title", title);
    setText(".lb-desc", desc);
    setText(".lb-client", client || "—");
    setText(".lb-tools", tools || "—");
    setText(".lb-year", year || "—");

    var visit = lightbox.querySelector(".lb-link");
    if (visit) {
      if (link) { visit.href = link; visit.style.display = ""; }
      else { visit.removeAttribute("href"); visit.style.display = "none"; }
    }
    if (id) countView("work", id);
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }
  function setText(sel, text) {
    var el = lightbox ? lightbox.querySelector(sel) : null;
    if (el) el.textContent = text || "";
  }
  function categoryFromKey(key) {
    var map = {
      "content-social": "Content & Social Media",
      "jujutsu": "Jujutsu Content",
      "radiography": "Radiography",
      "video": "Video Editing",
      "geographic": "Geographic Design"
    };
    return map[key] || key;
  }
  function openLightbox(card) {
    if (!lightbox) return;
    visibleWorks = currentWorks();
    fillLightbox(card);
    lightbox.classList.add("open");
    document.body.style.overflow = "hidden";
  }
  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove("open");
    document.body.style.overflow = "";
    var media = lightbox.querySelector(".lightbox-media");
    if (media) media.innerHTML = "";
  }
  function stepLightbox(dir) {
    if (!visibleWorks.length) return;
    var openCard = lightbox.querySelector(".lb-title");
    var currentTitle = openCard ? openCard.textContent : "";
    var idx = visibleWorks.findIndex(function (c) { return c.getAttribute("data-title") === currentTitle; });
    if (idx < 0) idx = 0;
    var next = visibleWorks[(idx + dir + visibleWorks.length) % visibleWorks.length];
    fillLightbox(next);
  }

  workCards.forEach(function (card) {
    card.addEventListener("click", function () { openLightbox(card); });
    card.addEventListener("keydown", function (ev) {
      if (ev.key === "Enter") openLightbox(card);
    });
  });
  if (lightbox) {
    lightbox.querySelector(".lightbox-close").addEventListener("click", closeLightbox);
    lightbox.addEventListener("click", function (ev) {
      if (ev.target === lightbox) closeLightbox();
    });
    lightbox.querySelector(".lightbox-nav.prev").addEventListener("click", function (ev) { ev.stopPropagation(); stepLightbox(-1); });
    lightbox.querySelector(".lightbox-nav.next").addEventListener("click", function (ev) { ev.stopPropagation(); stepLightbox(1); });
    document.addEventListener("keydown", function (ev) {
      if (!lightbox.classList.contains("open")) return;
      if (ev.key === "Escape") closeLightbox();
      if (ev.key === "ArrowLeft") stepLightbox(-1);
      if (ev.key === "ArrowRight") stepLightbox(1);
    });
  }

  /* ---------- View counter ---------- */
  var base = document.body.getAttribute("data-base") || "/";
  function countView(type, id) {
    try {
      fetch(base + "actions/view.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded", "X-Requested-With": "XMLHttpRequest" },
        body: "type=" + encodeURIComponent(type) + "&id=" + encodeURIComponent(id),
        keepalive: true
      }).catch(function () {});
    } catch (e) {}
  }
  var article = document.querySelector("[data-article-id]");
  if (article) countView("post", article.getAttribute("data-article-id"));

  /* ---------- Contact form (client-side checks) ---------- */
  var contactForm = document.getElementById("contactForm");
  if (contactForm) {
    contactForm.addEventListener("submit", function (ev) {
      var name = contactForm.querySelector("[name='name']");
      var email = contactForm.querySelector("[name='email']");
      var message = contactForm.querySelector("[name='message']");
      var ok = true;
      [name, email, message].forEach(function (f) {
        if (!f) return;
        var valid = f.value.trim().length > (f.name === "message" ? 9 : 2);
        if (f.name === "email") valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value.trim());
        f.style.borderColor = valid ? "" : "#ff4d6d";
        if (!valid) ok = false;
      });
      if (!ok) ev.preventDefault();
    });
  }

  /* ---------- Auto-hide alerts ---------- */
  document.querySelectorAll(".alert-x[data-autohide]").forEach(function (el) {
    setTimeout(function () {
      el.style.transition = "opacity .5s, transform .5s";
      el.style.opacity = "0";
      el.style.transform = "translateY(-8px)";
      setTimeout(function () { el.remove(); }, 550);
    }, 5200);
  });
})();
