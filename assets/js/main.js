// DOi PC front-end. Each feature is isolated so one failure never breaks the others.
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const safe = (name, fn) => { try { fn(); } catch (err) { console.error('[' + name + ']', err); } };
const peso = (n) => '₱' + Number(n).toLocaleString('en-PH');

function showToast(message, type = 'info') {
  const t = document.createElement('div');
  t.className = 'toast-notification ' + type;
  t.textContent = message;
  document.body.appendChild(t);
  setTimeout(() => { t.style.animation = 'slideInRight .5s ease reverse'; setTimeout(() => t.remove(), 500); }, 3500);
}

function confetti() {
  if (reduce) return;
  const colors = ['#FF2D37', '#3B5FFF', '#8FE84E', '#FFE31A'];
  for (let i = 0; i < 30; i++) {
    const c = document.createElement('div');
    c.style.cssText = 'position:fixed;width:10px;height:10px;top:50%;left:50%;border-radius:50%;pointer-events:none;z-index:10000;background:' + colors[i % 4];
    document.body.appendChild(c);
    const a = (Math.PI * 2 * i) / 30, v = 200 + Math.random() * 200;
    c.animate([{ transform: 'translate(0,0)', opacity: 1 }, { transform: 'translate(' + Math.cos(a) * v + 'px,' + (Math.sin(a) * v - 300) + 'px)', opacity: 0 }],
      { duration: 1000 + Math.random() * 500, easing: 'cubic-bezier(0,.9,.57,1)' }).onfinish = () => c.remove();
  }
}

// Scroll progress bar, nav shrink, back-to-top
safe('scroll', () => {
  const nav = $('#mainNav'), bar = $('#progress'), top = $('#backToTop');
  const update = () => {
    const y = scrollY, max = document.documentElement.scrollHeight - innerHeight;
    if (bar) bar.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
    if (nav) nav.classList.toggle('shrunk', y > 40);
    if (top) top.classList.toggle('show', y > 600);
  };
  addEventListener('scroll', update, { passive: true });
  update();
});

// Reveal on scroll and count-up numbers
safe('reveal', () => {
  const countUp = (el) => {
    const end = +el.dataset.count, suffix = el.dataset.suffix || '';
    if (reduce) { el.textContent = end + suffix; return; }
    const t0 = performance.now(), dur = 1500;
    const tick = (t) => {
      const p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  if (!reduce) $$('[data-count]').forEach(el => { el.textContent = '0' + (el.dataset.suffix || ''); });
  const io = new IntersectionObserver((entries) => entries.forEach(en => {
    if (!en.isIntersecting) return;
    io.unobserve(en.target);
    en.target.classList.add('in');
    if (en.target.dataset.count) countUp(en.target);
  }), { threshold: 0.25 });
  $$('.reveal,.reveal-image,[data-count]').forEach(el => io.observe(el));
});

// "Open now" badge, evaluated in Philippine time
safe('status', () => {
  const el = $('#openStatus'); if (!el) return;
  const open = +el.dataset.open, close = +el.dataset.close, days = el.dataset.days.split(',').map(Number);
  const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short', hour: 'numeric', hour12: false }).formatToParts(new Date());
  const wd = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(parts.find(p => p.type === 'weekday').value);
  const hr = +parts.find(p => p.type === 'hour').value % 24;
  const isOpen = days.includes(wd) && hr >= open && hr < close;
  const fmt = (h) => ((h + 11) % 12 + 1) + ':00 ' + (h < 12 ? 'AM' : 'PM');
  el.classList.add(isOpen ? 'is-open' : 'is-closed');
  el.querySelector('.status-text').textContent = isOpen ? 'Open now, until ' + fmt(close) : 'Closed now, message us anytime';
});

// Build planner
safe('planner', () => {
  const root = $('#planner-widget'); if (!root) return;
  const min = JSON.parse(root.dataset.min), use = $('#plUse'), range = $('#plBudget'), out = $('#plOut'), res = $('#plResult');
  const update = () => {
    const b = +range.value, m = min[use.value];
    out.textContent = peso(b);
    res.textContent = b >= m
      ? 'Good news: ' + peso(b) + ' is enough for a solid ' + use.value.toLowerCase() + ' build from our current stock.'
      : use.value + ' builds usually start around ' + peso(m) + '. We can still look for the best option near ' + peso(b) + '.';
  };
  use.addEventListener('input', update); range.addEventListener('input', update); update();
  $('#plSend').addEventListener('click', () => {
    $('#usecase').value = use.value;
    $('#budget').value = Number(range.value).toLocaleString('en-PH');
    $('#bundle_id').value = '';
    $('#quote').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
    setTimeout(() => $('#name').focus({ preventScroll: true }), 600);
  });
});

// Bundle and category "quote" buttons. One handler, prefills the form.
safe('quote-pick', () => $$('.quote-pick').forEach(btn => btn.addEventListener('click', () => {
  const d = btn.dataset;
  if (d.category) $('#usecase').value = d.category;
  $('#bundle_id').value = d.bundleId || '';
  if (d.price) $('#budget').value = Number(d.price).toLocaleString('en-PH');
  if (d.package) {
    const msg = $('#message');
    if (!msg.value.trim()) msg.value = "I'm interested in the " + d.package + ' bundle (' + peso(d.price) + '). Please confirm availability.';
    showToast(d.package + ' selected. Complete the form to request your quote.', 'success');
  }
  const wait = btn.hasAttribute('data-bs-dismiss') ? 450 : 0; // let the modal finish closing first
  setTimeout(() => $('#quote').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }), wait);
  setTimeout(() => $('#name').focus({ preventScroll: true }), wait + 700);
})));

// Gallery lightbox (uses whatever image the thumbnail is showing)
safe('lightbox', () => $('#lightbox').addEventListener('show.bs.modal', (e) => {
  const thumb = e.relatedTarget && e.relatedTarget.querySelector('img');
  const img = $('#lightboxImg');
  if (thumb) { img.src = thumb.currentSrc || thumb.src; img.alt = e.relatedTarget.dataset.alt || ''; }
}));

// Button ripple
safe('ripple', () => $$('.btn-accent').forEach(btn => btn.addEventListener('click', (e) => {
  if (reduce) return;
  const r = document.createElement('span'), box = btn.getBoundingClientRect();
  r.style.cssText = 'position:absolute;width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.5);pointer-events:none;animation:ripple-effect .6s ease-out;left:' + (e.clientX - box.left) + 'px;top:' + (e.clientY - box.top) + 'px';
  btn.appendChild(r);
  setTimeout(() => r.remove(), 600);
})));

// Quote form
safe('form', () => {
  const form = $('#quoteForm'), box = $('#formAlert');
  const show = (type, text) => { box.innerHTML = '<div class="alert alert-' + type + '"></div>'; box.firstChild.textContent = text; };
  const fields = form.querySelectorAll('input[required], select[required], textarea[required]');
  fields.forEach(f => {
    f.addEventListener('blur', () => { const ok = f.value.trim() !== ''; f.classList.toggle('is-valid', ok); f.classList.toggle('is-invalid', !ok); });
    f.addEventListener('input', () => { if (f.classList.contains('is-invalid') && f.value.trim()) { f.classList.remove('is-invalid'); f.classList.add('is-valid'); } });
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Sending...';
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      if (data.ok) {
        show('success', data.message); showToast('Quote request sent!', 'success'); confetti();
        form.reset(); fields.forEach(f => f.classList.remove('is-valid', 'is-invalid')); $('#bundle_id').value = '';
      } else {
        show('danger', data.message || 'Something went wrong. Please try again.');
      }
    } catch (err) {
      show('danger', 'Could not send your request. Please message us on Messenger instead.');
    }
    btn.disabled = false; btn.textContent = 'Send quote request';
  });
});
