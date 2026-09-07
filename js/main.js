/* ==========================================================================
   ابتكار للحلول الذكية — Ebitkar Smart Solutions
   ========================================================================== */
(function () {
  'use strict';

  var html = document.documentElement;

  /* ----------------------------------------------------------------------
     1. Language switch (AR / EN)
     --------------------------------------------------------------------- */
  var TITLES = {
    ar: 'ابتكار للحلول الذكية | تصميم مواقع وتطبيقات وتسويق رقمي',
    en: 'Ebitkar Smart Solutions | Web, Mobile Apps & Digital Marketing'
  };

  // Cache the Arabic original of every translatable node once.
  var nodes = document.querySelectorAll('[data-en]');
  nodes.forEach(function (el) { el.dataset.ar = el.innerHTML; });

  var ph = document.querySelectorAll('[data-en-placeholder]');
  ph.forEach(function (el) { el.dataset.arPlaceholder = el.getAttribute('placeholder') || ''; });

  var ar = document.querySelectorAll('[data-en-aria]');
  ar.forEach(function (el) { el.dataset.arAria = el.getAttribute('aria-label') || ''; });

  function setLang(lang) {
    var isEn = lang === 'en';

    html.setAttribute('lang', isEn ? 'en' : 'ar');
    html.setAttribute('dir', isEn ? 'ltr' : 'rtl');
    document.title = TITLES[isEn ? 'en' : 'ar'];

    nodes.forEach(function (el) {
      el.innerHTML = isEn ? el.dataset.en : el.dataset.ar;
    });
    ph.forEach(function (el) {
      el.setAttribute('placeholder', isEn ? el.dataset.enPlaceholder : el.dataset.arPlaceholder);
    });
    ar.forEach(function (el) {
      el.setAttribute('aria-label', isEn ? el.dataset.enAria : el.dataset.arAria);
    });

    document.querySelectorAll('.lang-switch button').forEach(function (b) {
      b.classList.toggle('on', b.dataset.lang === (isEn ? 'en' : 'ar'));
    });

    try { localStorage.setItem('ebitkar-lang', isEn ? 'en' : 'ar'); } catch (e) { /* private mode */ }
  }

  document.querySelectorAll('.lang-switch button').forEach(function (b) {
    b.addEventListener('click', function () { setLang(b.dataset.lang); });
  });

  // restore saved choice, else fall back to Arabic
  var saved = null;
  try { saved = localStorage.getItem('ebitkar-lang'); } catch (e) { /* ignore */ }
  if (saved === 'en') setLang('en');

  /* ----------------------------------------------------------------------
     2. Quick menu
     --------------------------------------------------------------------- */
  var burger = document.getElementById('burger');
  var menu = document.getElementById('quickMenu');
  var scrim = document.getElementById('scrim');

  function toggleMenu(open) {
    var willOpen = typeof open === 'boolean' ? open : !menu.classList.contains('open');
    menu.classList.toggle('open', willOpen);
    burger.classList.toggle('open', willOpen);
    scrim.classList.toggle('on', willOpen);
    burger.setAttribute('aria-expanded', String(willOpen));
    document.body.style.overflow = willOpen ? 'hidden' : '';
  }

  burger.addEventListener('click', function () { toggleMenu(); });
  scrim.addEventListener('click', function () { toggleMenu(false); });
  menu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { toggleMenu(false); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.classList.contains('open')) toggleMenu(false);
  });

  /* ----------------------------------------------------------------------
     3. Scroll reveal
     --------------------------------------------------------------------- */
  var reveals = document.querySelectorAll('.rv');

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ----------------------------------------------------------------------
     4. Testimonials slider
     --------------------------------------------------------------------- */
  var quotes = Array.prototype.slice.call(document.querySelectorAll('.quote'));
  var dotsBox = document.getElementById('dots');
  var current = 0;
  var timer = null;

  if (quotes.length && dotsBox) {
    quotes.forEach(function (q, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('role', 'tab');
      b.setAttribute('aria-label', 'Testimonial ' + (i + 1));
      if (i === 0) b.classList.add('on');
      b.addEventListener('click', function () { show(i); restart(); });
      dotsBox.appendChild(b);
    });

    var dots = dotsBox.querySelectorAll('button');

    function show(i) {
      current = (i + quotes.length) % quotes.length;
      quotes.forEach(function (q, n) { q.classList.toggle('on', n === current); });
      dots.forEach(function (d, n) {
        d.classList.toggle('on', n === current);
        d.setAttribute('aria-selected', String(n === current));
      });
    }
    function restart() {
      clearInterval(timer);
      timer = setInterval(function () { show(current + 1); }, 6500);
    }
    restart();

    var quotesBox = document.getElementById('quotes');
    quotesBox.addEventListener('mouseenter', function () { clearInterval(timer); });
    quotesBox.addEventListener('mouseleave', restart);
  }

  /* ----------------------------------------------------------------------
     5. Sticky top bar + back to top
     --------------------------------------------------------------------- */
  var toTop = document.getElementById('toTop');
  var topbar = document.getElementById('topbar');
  var tbName = document.getElementById('tbName');
  var tbNav = document.querySelectorAll('.tb-nav a');

  // the sections the bar walks through, with both labels
  var SECTIONS = [
    { id: 'top', ar: 'الرئيسية', en: 'Home' },
    { id: 'about', ar: 'من نحن', en: 'About Us' },
    { id: 'services', ar: 'خدماتنا', en: 'What We Do' },
    { id: 'spotlight', ar: 'مواقع مخصّصة', en: 'Bespoke Websites' },
    { id: 'work', ar: 'أعمالنا', en: 'Portfolio' },
    { id: 'process', ar: 'كيف نعمل', en: 'How We Work' },
    { id: 'testimonials', ar: 'آراء العملاء', en: 'Testimonials' },
    { id: 'team', ar: 'الفريق', en: 'The Team' },
    { id: 'contact', ar: 'تواصل معنا', en: 'Contact' }
  ];
  var currentSection = 0;

  function labelOf(i) {
    var s = SECTIONS[i];
    return html.getAttribute('lang') === 'en' ? s.en : s.ar;
  }

  function goTo(i) {
    var n = Math.max(0, Math.min(SECTIONS.length - 1, i));
    var el = document.getElementById(SECTIONS[n].id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.getElementById('tbPrev').addEventListener('click', function () { goTo(currentSection - 1); });
  document.getElementById('tbNext').addEventListener('click', function () { goTo(currentSection + 1); });

  function onScroll() {
    var y = window.scrollY;
    var vh = window.innerHeight;

    topbar.classList.toggle('on', y > vh * 0.8);
    document.body.classList.toggle('scrolled', y > vh * 0.8);
    toTop.classList.toggle('on', y > vh * 0.7);

    // which section occupies the top third of the screen?
    var found = 0;
    for (var i = 0; i < SECTIONS.length; i++) {
      var el = document.getElementById(SECTIONS[i].id);
      if (el && el.getBoundingClientRect().top <= vh * 0.34) found = i;
    }

    if (found !== currentSection) {
      currentSection = found;
      tbName.textContent = labelOf(found);
      var id = SECTIONS[found].id;
      tbNav.forEach(function (a) {
        a.classList.toggle('active', a.getAttribute('href') === '#' + id);
      });
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // keep the bar's label in the right language after a switch
  document.querySelectorAll('.lang-switch button').forEach(function (b) {
    b.addEventListener('click', function () { tbName.textContent = labelOf(currentSection); });
  });

  /* ----------------------------------------------------------------------
     6. Contact form  →  posts to contact.php, falls back to the mail client
     --------------------------------------------------------------------- */
  var form = document.getElementById('contactForm');
  var ok = document.getElementById('formOk');

  var MSG = {
    sending: { ar: 'جارٍ الإرسال…', en: 'Sending…' },
    sent:    { ar: 'وصلتنا رسالتك — سنعود إليك خلال يوم عمل واحد.',
               en: 'Got it — we will come back to you within one working day.' },
    tooFast: { ar: 'أرسلت رسالة للتو، انتظر قليلاً قبل إرسال أخرى.',
               en: 'You just sent a message — please wait a moment before sending another.' },
    failed:  { ar: 'تعذّر الإرسال. سنفتح لك برنامج البريد بدلاً من ذلك…',
               en: 'Could not send. Opening your mail app instead…' }
  };

  function say(key, tone) {
    var isEn = html.getAttribute('lang') === 'en';
    ok.textContent = MSG[key][isEn ? 'en' : 'ar'];
    ok.classList.add('on');
    ok.classList.toggle('warn', tone === 'warn');
  }

  function mailtoFallback(d) {
    var isEn = html.getAttribute('lang') === 'en';
    var subject = (isEn ? 'New project brief — ' : 'طلب مشروع جديد — ') + (d.get('service') || '');
    var body = [
      (isEn ? 'Name: ' : 'الاسم: ') + (d.get('name') || ''),
      (isEn ? 'Phone: ' : 'الهاتف: ') + (d.get('phone') || ''),
      (isEn ? 'Email: ' : 'البريد: ') + (d.get('email') || ''),
      (isEn ? 'Service: ' : 'الخدمة: ') + (d.get('service') || ''),
      '',
      (isEn ? 'Details:' : 'التفاصيل:'),
      (d.get('message') || '')
    ].join('\n');

    window.location.href = 'mailto:info@ebitkar.com'
      + '?subject=' + encodeURIComponent(subject)
      + '&body=' + encodeURIComponent(body);
  }

  if (form) {
    var btn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!form.reportValidity()) return;

      var d = new FormData(form);
      say('sending');
      if (btn) btn.disabled = true;

      fetch('contact.php', { method: 'POST', body: d })
        .then(function (r) {
          return r.json().catch(function () { throw new Error('not_json'); })
            .then(function (data) { return { status: r.status, data: data }; });
        })
        .then(function (res) {
          if (res.data && res.data.ok) {
            say('sent');
            form.reset();
          } else if (res.status === 429) {
            say('tooFast', 'warn');
          } else {
            throw new Error((res.data && res.data.error) || 'failed');
          }
        })
        .catch(function () {
          // no PHP, offline, or the mailer refused — hand it to the mail client
          say('failed', 'warn');
          mailtoFallback(d);
        })
        .then(function () {
          if (btn) btn.disabled = false;
        });
    });
  }

  /* ----------------------------------------------------------------------
     7. Current year
     --------------------------------------------------------------------- */
  var y = String(new Date().getFullYear());
  var yr = document.getElementById('yr');
  if (yr) yr.textContent = y;
  document.querySelectorAll('.yr').forEach(function (el) { el.textContent = y; });

})();
