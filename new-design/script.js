/* ============================================================
   ITX — Interactions
   ============================================================ */
(function () {
  "use strict";
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Navbar scroll state ---------- */
  const nav = document.getElementById("nav");
  const onScroll = () => {
    nav.classList.toggle("scrolled", window.scrollY > 24);
    const st = document.getElementById("scrollTop");
    if (st) st.classList.toggle("show", window.scrollY > 600);
  };
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile menu ---------- */
  const burger = document.getElementById("burger");
  const links = document.getElementById("navLinks");
  if (burger) {
    burger.addEventListener("click", () => {
      const open = links.classList.toggle("open");
      burger.classList.toggle("open", open);
      burger.setAttribute("aria-expanded", open);
    });
    links.querySelectorAll("a").forEach((a) =>
      a.addEventListener("click", () => {
        links.classList.remove("open");
        burger.classList.remove("open");
      })
    );
  }

  /* ---------- Active link on scroll (scroll spy) ---------- */
  const sections = [...document.querySelectorAll("section[id]")];
  const navAnchors = [...document.querySelectorAll(".nav-links a")];
  const spy = () => {
    const y = window.scrollY + 120;
    let cur = sections[0]?.id;
    for (const s of sections) if (s.offsetTop <= y) cur = s.id;
    navAnchors.forEach((a) =>
      a.classList.toggle("active", a.getAttribute("href") === "#" + cur)
    );
  };
  window.addEventListener("scroll", spy, { passive: true });
  spy();

  /* ---------- Scroll reveal ---------- */
  const reveals = document.querySelectorAll(".reveal");
  if (reduce) {
    reveals.forEach((r) => r.classList.add("in"));
  } else {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add("in");
            // trigger skill bars
            if (e.target.classList.contains("skills")) animateSkills();
            io.unobserve(e.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -8% 0px" }
    );
    reveals.forEach((r) => io.observe(r));
  }

  function animateSkills() {
    document.querySelectorAll(".skill .bar i").forEach((bar) => {
      bar.style.width = bar.dataset.val + "%";
    });
  }
  if (reduce) animateSkills();

  /* ---------- Counters ---------- */
  const counters = document.querySelectorAll(".num[data-target]");
  const runCounter = (el) => {
    const target = parseFloat(el.dataset.target);
    const dur = 1800;
    const start = performance.now();
    const dec = (target % 1 !== 0) ? 1 : 0;
    const step = (now) => {
      const p = Math.min((now - start) / dur, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      const val = (target * eased).toFixed(dec);
      el.querySelector(".val").textContent = Number(val).toLocaleString("en-US");
      if (p < 1) requestAnimationFrame(step);
      else el.querySelector(".val").textContent = Number(target).toLocaleString("en-US");
    };
    requestAnimationFrame(step);
  };
  if (counters.length) {
    const cio = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            reduce
              ? (e.target.querySelector(".val").textContent = Number(e.target.dataset.target).toLocaleString("en-US"))
              : runCounter(e.target);
            cio.unobserve(e.target);
          }
        });
      },
      { threshold: 0.5 }
    );
    counters.forEach((c) => cio.observe(c));
  }

  /* ---------- Portfolio filter ---------- */
  const filterBtns = document.querySelectorAll(".filter-btn");
  const works = document.querySelectorAll(".work");
  filterBtns.forEach((btn) =>
    btn.addEventListener("click", () => {
      filterBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      const f = btn.dataset.filter;
      works.forEach((w) => {
        const match = f === "all" || w.dataset.cat === f;
        w.classList.toggle("hide", !match);
      });
    })
  );

  /* ---------- Contact form (demo) ---------- */
  const form = document.getElementById("contactForm");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const btn = form.querySelector("button[type=submit]");
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.textContent = I18N[currentLang].sending;
      setTimeout(() => {
        btn.textContent = I18N[currentLang].sent;
        btn.style.background = "#1F8A5B";
        setTimeout(() => {
          btn.innerHTML = orig;
          btn.disabled = false;
          btn.style.background = "";
          form.reset();
        }, 2200);
      }, 1200);
    });
  }

  /* ---------- Scroll to top ---------- */
  const st = document.getElementById("scrollTop");
  if (st) st.addEventListener("click", () => window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" }));

  /* ---------- Hero logo assembly ---------- */
  const tris = document.querySelectorAll(".mark-svg .tri");
  tris.forEach((t, i) => {
    if (reduce) { t.style.opacity = 1; return; }
    t.animate(
      [
        { opacity: 0, transform: "translate(0,0) scale(.2) rotate(140deg)" },
        { opacity: 1, transform: "translate(0,0) scale(1) rotate(0deg)" },
      ],
      { duration: 900, delay: 200 + i * 130, fill: "forwards", easing: "cubic-bezier(.22,.9,.3,1)" }
    );
  });

  /* ============================================================
     AR / EN toggle
     ============================================================ */
  window.currentLang = "ar";
  const langBtn = document.getElementById("langToggle");

  function applyLang(lang) {
    currentLang = lang;
    const html = document.documentElement;
    html.lang = lang;
    html.dir = lang === "ar" ? "rtl" : "ltr";
    document.querySelectorAll("[data-ar]").forEach((el) => {
      const val = el.getAttribute("data-" + lang);
      if (val !== null) {
        if (el.hasAttribute("data-attr")) {
          el.setAttribute(el.getAttribute("data-attr"), val);
        } else {
          el.innerHTML = val;
        }
      }
    });
    if (langBtn) langBtn.querySelector(".lang-label").textContent = lang === "ar" ? "EN" : "ع";
    try { localStorage.setItem("itx_lang", lang); } catch (e) {}
  }

  if (langBtn) {
    langBtn.addEventListener("click", () => applyLang(currentLang === "ar" ? "en" : "ar"));
  }
  let saved = "ar";
  try { saved = localStorage.getItem("itx_lang") || "ar"; } catch (e) {}
  applyLang(saved);
})();

/* button label strings (used by form) */
var I18N = {
  ar: { sending: "جارٍ الإرسال…", sent: "✓ تم الإرسال بنجاح" },
  en: { sending: "Sending…", sent: "✓ Sent successfully" },
};
var currentLang = "ar";
