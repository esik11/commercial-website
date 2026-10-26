const $ = (s) => document.querySelector(s);
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

// Scroll progress, sticky nav shrink, back-to-top
const nav = $('#mainNav'), bar = $('#progress'), toTop = $('#backToTop');
function onScroll() {
  const y = window.scrollY, max = document.documentElement.scrollHeight - innerHeight;
  bar.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
  nav.classList.toggle('shrunk', y > 40);
  if (toTop) toTop.classList.toggle('show', y > 600);
}
addEventListener('scroll', onScroll, { passive: true }); onScroll();
if (toTop) toTop.addEventListener('click', (e) => { e.preventDefault(); scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

// Count-up stats and reveal-on-scroll
const io = new IntersectionObserver((entries) => entries.forEach(en => {
  if (!en.isIntersecting) return;
  io.unobserve(en.target);
  if (en.target.classList.contains('reveal')) en.target.classList.add('in');
  if (en.target.dataset.count) countUp(en.target);
}), { threshold: 0.3 });
document.querySelectorAll('.reveal,[data-count]').forEach(el => io.observe(el));

function countUp(el) {
  const end = +el.dataset.count, suffix = el.dataset.suffix || '';
  if (reduce) { el.textContent = end + suffix; return; }
  const t0 = performance.now(), dur = 1400;
  (function tick(t) {
    const p = Math.min((t - t0) / dur, 1);
    el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suffix;
    if (p < 1) requestAnimationFrame(tick);
  })(t0);
}

// "Open now" badge, evaluated in Philippine time
(function () {
  const el = $('#openStatus'); if (!el) return;
  const open = +el.dataset.open, close = +el.dataset.close, days = el.dataset.days.split(',').map(Number);
  const parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short', hour: 'numeric', hour12: false }).formatToParts(new Date());
  const wd = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].indexOf(parts.find(p => p.type === 'weekday').value);
  const hr = +parts.find(p => p.type === 'hour').value % 24;
  const isOpen = days.includes(wd) && hr >= open && hr < close;
  const fmt = (h) => ((h + 11) % 12 + 1) + ':00 ' + (h < 12 ? 'AM' : 'PM');
  el.classList.add(isOpen ? 'is-open' : 'is-closed');
  el.querySelector('.status-text').textContent = isOpen ? 'Open now, until ' + fmt(close) : 'Closed now, message us anytime';
})();

// Build planner widget
(function () {
  const root = $('#planner-widget'); if (!root) return;
  const min = JSON.parse(root.dataset.min), use = $('#plUse'), range = $('#plBudget'), out = $('#plOut'), res = $('#plResult');
  const peso = (n) => '₱' + Number(n).toLocaleString('en-PH');
  function update() {
    const b = +range.value, m = min[use.value];
    out.textContent = peso(b);
    res.textContent = b >= m
      ? 'Good news: ' + peso(b) + ' is enough for a solid ' + use.value.toLowerCase() + ' build from our current stock.'
      : use.value + ' builds usually start around ' + peso(m) + '. We can still look for the best option near ' + peso(b) + '.';
  }
  use.addEventListener('input', update); range.addEventListener('input', update); update();
  $('#plSend').addEventListener('click', () => {
    $('#usecase').value = use.value; $('#budget').value = Number(range.value).toLocaleString('en-PH');
    $('#quote').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
    setTimeout(() => $('#name').focus({ preventScroll: true }), 500);
  });
})();

// Package buttons preselect build type
document.querySelectorAll('.quote-pick').forEach(btn => btn.addEventListener('click', () => {
  $('#usecase').value = btn.dataset.package;
}));

// Gallery lightbox
$('#lightbox').addEventListener('show.bs.modal', (e) => {
  const img = $('#lightboxImg'); img.src = e.relatedTarget.dataset.src; img.alt = e.relatedTarget.dataset.alt;
});

// Quote form (AJAX)
const form = $('#quoteForm'), alertBox = $('#formAlert');
function show(type, text) {
  alertBox.innerHTML = '<div class="alert alert-' + type + '"></div>';
  alertBox.firstChild.textContent = text;
}
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const btn = form.querySelector('button[type=submit]');
  btn.disabled = true; btn.textContent = 'Sending...';
  try {
    const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
    const data = await res.json();
    if (data.ok) { show('success', data.message); form.reset(); }
    else { show('danger', data.message || 'Something went wrong. Please try again.'); }
  } catch (err) {
    show('danger', 'Could not send your request. Please message us on Messenger instead.');
  }
  btn.disabled = false; btn.textContent = 'Send quote request';
});


// Page Loader
window.addEventListener('load', () => {
  const loader = $('#pageLoader');
  if (loader) {
    setTimeout(() => {
      loader.classList.add('hidden');
      setTimeout(() => loader.remove(), 500);
    }, 300);
  }
});

// Enhanced Form Validation with Visual Feedback
(function() {
  const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
  inputs.forEach(input => {
    input.addEventListener('blur', () => {
      if (input.value.trim()) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
      }
    });
    
    input.addEventListener('input', () => {
      if (input.classList.contains('is-invalid') && input.value.trim()) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      }
    });
  });
})();

// Smooth Scroll for all anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = $(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
    }
  });
});

// Add animation to service cards on hover
document.querySelectorAll('.service-card').forEach(card => {
  card.addEventListener('mouseenter', function() {
    this.style.transform = 'translateY(-8px) scale(1.02)';
  });
  card.addEventListener('mouseleave', function() {
    this.style.transform = 'translateY(0) scale(1)';
  });
});

// Initialize tooltips if Bootstrap tooltips are used
document.addEventListener('DOMContentLoaded', function() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
});

// Keyboard navigation enhancement
document.addEventListener('keydown', (e) => {
  // Press 'Escape' to close modals or return to top
  if (e.key === 'Escape' && window.scrollY > 600) {
    scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
  }
});

// Console Easter Egg for developers
console.log('%cDOi PC Computer Parts & Accessories', 'font-size:24px;font-weight:bold;color:#FF2D37');
console.log('%cLooking for a custom PC build? We can help! 🖥️✨', 'font-size:14px;color:#7B96FF');
console.log('%cWebsite built with ❤️ for the General Santos community', 'font-size:12px;color:#8FE84E');


// ===== PROFESSIONAL ENHANCEMENTS =====

// Enhanced Button Click Ripple Effect
document.querySelectorAll('.btn-accent').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const ripple = document.createElement('span');
    ripple.style.cssText = `
      position: absolute;
      width: 20px;
      height: 20px;
      background: rgba(255,255,255,.5);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
      animation: ripple-effect .6s ease-out;
    `;
    const rect = this.getBoundingClientRect();
    ripple.style.left = (e.clientX - rect.left) + 'px';
    ripple.style.top = (e.clientY - rect.top) + 'px';
    this.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  });
});

// Smooth Card 3D Tilt Effect
document.querySelectorAll('.service-card, .package, .why').forEach(card => {
  card.addEventListener('mousemove', function(e) {
    if (reduce) return;
    const rect = this.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    const rotateX = ((y - centerY) / centerY) * 5;
    const rotateY = ((centerX - x) / centerX) * 5;
    this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
  });
  
  card.addEventListener('mouseleave', function() {
    this.style.transform = '';
  });
});

// Live Stock Counter Animation
(function() {
  const counter = $('.stock-counter');
  if (!counter) return;
  
  setInterval(() => {
    const current = parseInt(counter.textContent.match(/\d+/)[0]);
    const variation = Math.random() > 0.5 ? 1 : -1;
    const newCount = Math.max(10, Math.min(20, current + variation));
    counter.innerHTML = `<span class="dot"></span> ${newCount}+ Gaming PCs Available Now`;
  }, 30000); // Update every 30 seconds
})();

// Enhanced Stat Counter with Easing
function countUp(el) {
  const end = +el.dataset.count, suffix = el.dataset.suffix || '';
  if (reduce) { el.textContent = end + suffix; return; }
  const t0 = performance.now(), dur = 2000;
  (function tick(t) {
    const p = Math.min((t - t0) / dur, 1);
    // Ease out cubic for smoother animation
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = Math.round(end * eased) + suffix;
    if (p < 1) requestAnimationFrame(tick);
  })(t0);
}

// Form Success Confetti Effect (lightweight)
function showSuccessConfetti() {
  const colors = ['#FF2D37', '#3B5FFF', '#8FE84E', '#FFE31A'];
  for (let i = 0; i < 30; i++) {
    const confetti = document.createElement('div');
    confetti.style.cssText = `
      position: fixed;
      width: 10px;
      height: 10px;
      background: ${colors[Math.floor(Math.random() * colors.length)]};
      top: 50%;
      left: 50%;
      opacity: 1;
      border-radius: 50%;
      pointer-events: none;
      z-index: 10000;
    `;
    document.body.appendChild(confetti);
    
    const angle = (Math.PI * 2 * i) / 30;
    const velocity = 200 + Math.random() * 200;
    const tx = Math.cos(angle) * velocity;
    const ty = Math.sin(angle) * velocity - 300;
    
    confetti.animate([
      { transform: 'translate(0, 0)', opacity: 1 },
      { transform: `translate(${tx}px, ${ty}px)`, opacity: 0 }
    ], {
      duration: 1000 + Math.random() * 500,
      easing: 'cubic-bezier(0, .9, .57, 1)'
    }).onfinish = () => confetti.remove();
  }
}

// Enhanced Form Submission with Confetti
const originalFormSubmit = form.onsubmit;
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!form.checkValidity()) { 
    form.reportValidity(); 
    return; 
  }
  const btn = form.querySelector('button[type=submit]');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.classList.add('btn-loading');
  btn.textContent = 'Sending...';
  
  try {
    const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
    const data = await res.json();
    if (data.ok) { 
      show('success', data.message); 
      form.reset();
      showSuccessConfetti(); // Add confetti on success
    }
    else { 
      show('danger', data.message || 'Something went wrong. Please try again.'); 
    }
  } catch (err) {
    show('danger', 'Could not send your request. Please message us on Messenger instead.');
  }
  
  btn.disabled = false;
  btn.classList.remove('btn-loading');
  btn.textContent = originalText;
});

// Smooth Parallax Effect on Hero
if (!reduce) {
  window.addEventListener('scroll', () => {
    const scrolled = window.scrollY;
    const hero = $('.hero');
    if (hero && scrolled < window.innerHeight) {
      hero.style.transform = `translateY(${scrolled * 0.5}px)`;
    }
  }, { passive: true });
}

// Add Scroll Indicator to Hero
if ($('.hero')) {
  const scrollInd = document.createElement('div');
  scrollInd.className = 'scroll-indicator';
  scrollInd.setAttribute('aria-label', 'Scroll down');
  $('.hero').appendChild(scrollInd);
}

// Enhanced Package Card Interactions
document.querySelectorAll('.package').forEach((pkg, idx) => {
  pkg.style.animationDelay = `${idx * 0.1}s`;
  
  // Add hover sound effect (optional - disabled by default)
  pkg.addEventListener('mouseenter', function() {
    // Uncomment to add sound: new Audio('data:audio/wav;base64,...').play();
  });
});

// Live Time Update for Business Hours
setInterval(() => {
  const status = $('#openStatus');
  if (!status) return;
  
  const open = +status.dataset.open;
  const close = +status.dataset.close;
  const days = status.dataset.days.split(',').map(Number);
  
  const parts = new Intl.DateTimeFormat('en-US', { 
    timeZone: 'Asia/Manila', 
    weekday: 'short', 
    hour: 'numeric', 
    hour12: false 
  }).formatToParts(new Date());
  
  const wd = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].indexOf(
    parts.find(p => p.type === 'weekday').value
  );
  const hr = +parts.find(p => p.type === 'hour').value % 24;
  
  const isOpen = days.includes(wd) && hr >= open && hr < close;
  const fmt = (h) => ((h + 11) % 12 + 1) + ':00 ' + (h < 12 ? 'AM' : 'PM');
  
  status.classList.remove('is-open', 'is-closed');
  status.classList.add(isOpen ? 'is-open' : 'is-closed');
  status.querySelector('.status-text').textContent = isOpen 
    ? `Open now, until ${fmt(close)}` 
    : 'Closed now, message us anytime';
}, 60000); // Update every minute

// Performance: Lazy Load Images
if ('IntersectionObserver' in window) {
  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
        }
        imageObserver.unobserve(img);
      }
    });
  });
  
  document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}


// ===== ADDITIONAL VISUAL ENHANCEMENTS =====

// Particle Effect Generator for Hero
(function() {
  const hero = $('.hero');
  if (!hero || reduce) return;
  
  const container = document.createElement('div');
  container.className = 'particles-container';
  hero.insertBefore(container, hero.firstChild);
  
  // Create 20 floating particles
  for (let i = 0; i < 20; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    particle.style.animationDelay = Math.random() * 5 + 's';
    container.appendChild(particle);
  }
  
  // Add glow particles
  const glowColors = ['red', 'blue', 'green'];
  glowColors.forEach(color => {
    const glow = document.createElement('div');
    glow.className = `glow-particle ${color}`;
    container.appendChild(glow);
  });
})();

// Before/After Image Slider
function initBeforeAfterSlider(container) {
  const handle = container.querySelector('.slider-handle');
  const afterImage = container.querySelector('.after-image');
  let isDragging = false;
  
  function updatePosition(e) {
    const rect = container.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const percentage = Math.max(0, Math.min(100, (x / rect.width) * 100));
    
    afterImage.style.width = percentage + '%';
    handle.style.left = percentage + '%';
  }
  
  container.addEventListener('mousedown', () => isDragging = true);
  container.addEventListener('touchstart', () => isDragging = true);
  
  document.addEventListener('mousemove', (e) => {
    if (isDragging) updatePosition(e);
  });
  
  document.addEventListener('touchmove', (e) => {
    if (isDragging) updatePosition(e);
  });
  
  document.addEventListener('mouseup', () => isDragging = false);
  document.addEventListener('touchend', () => isDragging = false);
  
  container.addEventListener('click', updatePosition);
}

// Initialize all before/after sliders
document.querySelectorAll('.before-after-slider').forEach(initBeforeAfterSlider);

// Smooth Page Transitions
function pageTransition(callback) {
  const overlay = document.createElement('div');
  overlay.className = 'page-transition active';
  document.body.appendChild(overlay);
  
  setTimeout(() => {
    if (callback) callback();
    setTimeout(() => {
      overlay.classList.remove('active');
      setTimeout(() => overlay.remove(), 400);
    }, 100);
  }, 400);
}

// Enhanced Image Reveal on Scroll
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.2 });

document.querySelectorAll('.reveal-image').forEach(img => {
  revealObserver.observe(img);
});

// Toast Notification System
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast-notification ${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideInRight .5s ease reverse';
    setTimeout(() => toast.remove(), 500);
  }, 3000);
}

// Optimistic UI for Form Submission
function addGhostItem(data) {
  const container = $('#quote-list');
  if (!container) return;
  
  const ghost = document.createElement('div');
  ghost.className = 'ghost-item';
  ghost.innerHTML = `<p>${data.name} - ${data.usecase}</p>`;
  container.prepend(ghost);
  
  return ghost;
}

// Enhanced Form with Optimistic Updates
const enhancedFormHandler = form.onsubmit;
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const formData = new FormData(form);
  const data = Object.fromEntries(formData);
  
  // Create ghost item
  const ghost = addGhostItem(data);
  
  // Show toast
  showToast('Sending your quote request...', 'info');
  
  // Continue with actual submission...
});

// Video Background Auto-play
document.querySelectorAll('.video-bg-container video').forEach(video => {
  video.muted = true;
  video.loop = true;
  video.play().catch(() => {
    // Autoplay failed, hide video
    video.style.display = 'none';
  });
});

// Enhanced Scroll Performance with RequestAnimationFrame
let ticking = false;
let lastScrollY = 0;

window.addEventListener('scroll', () => {
  lastScrollY = window.scrollY;
  if (!ticking) {
    window.requestAnimationFrame(() => {
      // Perform scroll-based animations here
      const scrollPercent = lastScrollY / (document.documentElement.scrollHeight - window.innerHeight);
      
      // Update any scroll-dependent elements
      document.querySelectorAll('.parallax-element').forEach(el => {
        el.style.transform = `translateY(${scrollPercent * 100}px)`;
      });
      
      ticking = false;
    });
    ticking = true;
  }
}, { passive: true });

// Loading Skeleton Replacement
function replaceSkeletons() {
  document.querySelectorAll('.skeleton').forEach(skeleton => {
    setTimeout(() => {
      skeleton.classList.add('loaded');
      skeleton.style.animation = 'none';
      skeleton.style.background = 'transparent';
    }, 1000 + Math.random() * 1000);
  });
}

// Call after content loads
window.addEventListener('load', () => {
  setTimeout(replaceSkeletons, 500);
});

// Intersection Observer for Performance
const performanceObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      // Trigger animations or load content
      entry.target.classList.add('visible');
      
      // If it's a heavy component, initialize it now
      if (entry.target.dataset.component) {
        // Initialize component
        console.log('Initializing:', entry.target.dataset.component);
      }
    }
  });
}, { 
  threshold: 0.1,
  rootMargin: '50px'
});

// Observe sections for performance
document.querySelectorAll('section[id]').forEach(section => {
  performanceObserver.observe(section);
});

// Preload Critical Images
function preloadImages(urls) {
  urls.forEach(url => {
    const img = new Image();
    img.src = url;
  });
}

// Preload hero and featured images
const criticalImages = [
  'assets/img/doi-pc_logo.jpg',
  // Add more critical images
];
preloadImages(criticalImages);

// Enhanced Hover States with Cursor
document.addEventListener('mousemove', (e) => {
  document.querySelectorAll('.interactive-card').forEach(card => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    card.style.setProperty('--mouse-x', x + 'px');
    card.style.setProperty('--mouse-y', y + 'px');
  });
});

// Memory Cleanup
window.addEventListener('beforeunload', () => {
  // Clean up observers
  if (io) io.disconnect();
  if (revealObserver) revealObserver.disconnect();
  if (performanceObserver) performanceObserver.disconnect();
});

console.log('✨ All professional enhancements loaded successfully!');


// ===== BUNDLE FILTERING SYSTEM =====
console.log('🚀 Bundle system code loaded!');

// Simple initialization without complex dependencies
function initBundleSystem() {
  console.log('🔧 initBundleSystem called!');
  
  const bundlesSection = document.getElementById('bundlesSection');
  const viewBundleBtns = document.querySelectorAll('.view-bundles-btn');
  const backBtn = document.querySelector('.back-to-categories');
  const packageCards = document.querySelectorAll('.package-selector');
  
  console.log('🔍 Bundle System Debug:', {
    bundlesSection: !!bundlesSection,
    viewBundleBtns: viewBundleBtns.length,
    backBtn: !!backBtn,
    packageCards: packageCards.length
  });
  
  if (!bundlesSection) {
    console.error('❌ Bundles section (#bundlesSection) not found!');
    return;
  }
  
  if (viewBundleBtns.length === 0) {
    console.error('❌ No view bundle buttons found!');
    return;
  }
  
  // View specific category bundles
  viewBundleBtns.forEach((btn, index) => {
    console.log(`Adding click handler to button ${index + 1}:`, btn.dataset.category);
    
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const category = this.dataset.category;
      console.log('✅ Button clicked! Category:', category);
      showBundles(category);
    });
  });
  
  // Also make entire card clickable
  packageCards.forEach((card, index) => {
    console.log(`Adding click handler to card ${index + 1}:`, card.dataset.category);
    
    card.addEventListener('click', function(e) {
      // Don't trigger if clicking the button itself
      if (e.target.closest('.view-bundles-btn')) {
        console.log('Clicked on button, skipping card handler');
        return;
      }
      
      const category = this.dataset.category;
      console.log('✅ Card clicked! Category:', category);
      showBundles(category);
    });
  });
  
  // Back to categories
  if (backBtn) {
    backBtn.addEventListener('click', function() {
      console.log('✅ Back button clicked');
      hideBundles();
    });
  }
  
  function showBundles(category) {
    console.log('📦 Showing bundles for:', category);
    
    // Hide all category cards with animation
    packageCards.forEach(card => {
      card.style.transition = 'all .3s ease';
      card.style.opacity = '0';
      card.style.transform = 'translateY(-20px)';
    });
    
    setTimeout(() => {
      packageCards.forEach(card => {
        card.closest('.col-md-4').style.display = 'none';
      });
      
      // Show bundles section
      bundlesSection.style.display = 'block';
      
      // Hide all bundle categories first
      document.querySelectorAll('.bundle-category').forEach(cat => {
        cat.style.display = 'none';
      });
      
      // Show selected category
      const selectedCategory = document.querySelector(`.bundle-category[data-category="${category}"]`);
      console.log('Selected category element:', selectedCategory);
      
      if (selectedCategory) {
        setTimeout(() => {
          selectedCategory.style.display = 'block';
          
          // Animate bundle cards in
          const bundleCards = selectedCategory.querySelectorAll('.reveal');
          console.log('Found bundle cards:', bundleCards.length);
          
          bundleCards.forEach((card, idx) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
              card.style.transition = 'all .6s cubic-bezier(.16,.8,.3,1)';
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            }, idx * 100);
          });
          
          // Smooth scroll to bundles
          setTimeout(() => {
            bundlesSection.scrollIntoView({ 
              behavior: 'smooth', 
              block: 'start' 
            });
          }, 200);
          
          // Show toast notification
          if (typeof showToast === 'function') {
            showToast(`Showing ${category} bundles`, 'info');
          }
          
          console.log('✅ Bundles displayed successfully');
        }, 100);
      } else {
        console.error('❌ Category not found:', category);
      }
    }, 400);
  }
  
  function hideBundles() {
    console.log('⬅️ Hiding bundles, returning to categories');
    
    // Animate bundles out
    const visibleBundles = document.querySelectorAll('.bundle-category[style*="display: block"] .reveal');
    visibleBundles.forEach((card, idx) => {
      setTimeout(() => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(-30px)';
      }, idx * 50);
    });
    
    // Hide bundles section
    setTimeout(() => {
      bundlesSection.style.display = 'none';
      
      // Show category cards again
      packageCards.forEach((card, idx) => {
        const col = card.closest('.col-md-4');
        col.style.display = 'block';
        
        setTimeout(() => {
          card.style.transition = 'all .6s cubic-bezier(.16,.8,.3,1)';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, idx * 100);
      });
      
      // Scroll to packages section
      setTimeout(() => {
        const packagesSection = document.getElementById('packages');
        if (packagesSection) {
          packagesSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
          });
        }
      }, 300);
      
      console.log('✅ Returned to categories');
    }, 400);
  }
  
  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && bundlesSection.style.display === 'block') {
      console.log('⌨️ ESC key pressed, hiding bundles');
      hideBundles();
    }
  });
  
  console.log('✅ Bundle filtering system initialized successfully!');
}

// Try multiple initialization methods to ensure it works
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initBundleSystem);
} else {
  // DOM already loaded
  initBundleSystem();
}

// Also try on window load as backup
window.addEventListener('load', function() {
  // Only init if not already initialized
  if (document.querySelectorAll('.view-bundles-btn').length > 0 && 
      !document.querySelector('.view-bundles-btn').onclick) {
    console.log('🔄 Initializing bundle system on window.load');
    initBundleSystem();
  }
});

// Enhanced Quote Button for Bundles
document.querySelectorAll('.bundle-card .quote-pick').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const bundleName = this.dataset.package;
    const bundlePrice = this.closest('.bundle-card').querySelector('.price').textContent;
    
    // Pre-fill form
    const usecaseField = $('#usecase');
    const budgetField = $('#budget');
    const messageField = $('#message');
    
    if (usecaseField) {
      // Extract category from bundle name
      const category = this.closest('.bundle-category').dataset.category;
      usecaseField.value = category;
    }
    
    if (budgetField) {
      budgetField.value = bundlePrice.replace(/[^0-9]/g, '');
    }
    
    if (messageField && messageField.value === '') {
      messageField.value = `I'm interested in the ${bundleName} bundle. Please provide more details.`;
    }
    
    // Show toast
    if (typeof showToast === 'function') {
      showToast(`${bundleName} selected! Scroll down to submit quote.`, 'success');
    }
    
    // Smooth scroll to quote form
    setTimeout(() => {
      $('#quote').scrollIntoView({ 
        behavior: reduce ? 'auto' : 'smooth' 
      });
      
      // Focus on name field
      setTimeout(() => {
        const nameField = $('#name');
        if (nameField) nameField.focus({ preventScroll: true });
      }, 800);
    }, 300);
  });
});
