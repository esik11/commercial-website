<?php
require __DIR__ . '/includes/header.php';

// Bundles come from the database and only appear while every part is in stock.
$bundlesByCat = ['Office' => [], 'Gaming' => [], 'Workstation' => []];
$liveCount = 0;
try {
  foreach (fetch_bundles(getDB(), true) as $b) { $bundlesByCat[$b['category']][] = $b; $liveCount++; }
} catch (Throwable $ex) {
  error_log('Could not load bundles: ' . $ex->getMessage());
}
foreach ($bundlesByCat as $cat => $list) {
  if ($list) { $minimums[$cat] = (int)min(array_map('floatval', array_column($list, 'price'))); }
}
function ph(int $i): string {
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#12213A"/><text x="400" y="310" font-family="Arial" font-size="40" fill="#8FE84E" text-anchor="middle">PC Build ' . $i . '</text></svg>';
  return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}
?>

<section class="hero text-white">
  <div class="particles-container" aria-hidden="true">
    <?php for ($p = 0; $p < 12; $p++): ?><span class="particle" style="left:<?= 5 + ($p * 8) ?>%;top:<?= 10 + (($p * 17) % 70) ?>%"></span><?php endfor; ?>
  </div>
  <div class="container hero-inner">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-meta hero-in">
          <p id="openStatus" class="status-pill mb-0" data-open="<?= (int)$site['open'] ?>" data-close="<?= (int)$site['close'] ?>" data-days="<?= e($site['days']) ?>"><span class="dot"></span><span class="status-text">Open <?= e($site['hours']) ?></span></p>
          <span id="localClock" class="local-clock" aria-live="polite">Gensan time</span>
        </div>
        <?php if (!empty($site['trust_badge'])): ?><div class="mb-3 hero-in"><span class="trust-badge"><?= e($site['trust_badge']) ?></span></div><?php endif; ?>
        <p class="brand-kicker hero-in"><span class="wordmark"><b class="wm-d">D</b><b class="wm-o">O</b><b class="wm-i">i</b> <b class="wm-pc">PC</b></span></p>
        <h1 class="display-4 hero-in">Your next PC, built from parts on our shelf today.</h1>
        <p class="lead mt-3 hero-in"><?= e(str_replace('*', '', $site['tagline'])) ?></p>
        <div class="d-flex flex-wrap gap-2 mt-4 hero-in">
          <a href="#quote" class="btn btn-accent btn-lg">Get a free build quote</a>
          <a href="<?= e($site['messenger']) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg">Ask on Messenger</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-visual hero-in">
          <img src="logofolder/doi-pc-logo.jpg" alt="DOi PC Computer Parts and Accessories Trading logo" class="img-fluid" onerror="this.style.display='none'">
          <div class="spec-card">
            <p class="spec-title mb-2">Build sheet</p>
            <dl class="mb-0">
              <div><dt>Parts</dt><dd><span class="badge-stock">In stock</span> matched to your budget</dd></div>
              <div><dt>Tested</dt><dd>Stress-tested before handover</dd></div>
              <div><dt>Warranty</dt><dd>Parts and workmanship</dd></div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container stats-strip">
    <div class="row g-3 text-center">
      <?php foreach ($stats as [$label, $num, $suffix]): ?>
      <div class="col-6 col-md-3"><div class="stat"><span class="stat-n" data-count="<?= (int)$num ?>" data-suffix="<?= e($suffix) ?>"><?= (int)$num . e($suffix) ?></span><span class="stat-l"><?= e($label) ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="brands" aria-label="Brands we carry">
  <div class="marquee">
    <?php for ($copy = 0; $copy < 2; $copy++): ?>
    <ul class="marquee-track" <?= $copy ? 'aria-hidden="true"' : '' ?>>
      <?php foreach ($brands as $b): ?><li><?= e($b) ?></li><?php endforeach; ?>
    </ul>
    <?php endfor; ?>
  </div>
</section>

<section id="services" class="py-6 band">
  <div class="container">
    <p class="section-kicker reveal">Services</p>
    <h2 class="section-head reveal mb-2">What we do</h2>
    <p class="section-lead reveal mb-4">Parts on the shelf, builds on the bench, and support when something needs fixing.</p>
    <div class="row g-4">
      <?php $si = 0; foreach ($services as [$title, $text]): ?>
      <div class="col-md-6 col-lg-4 reveal reveal-delay-<?= min(3, ($si % 3) + 1) ?>"><article class="card h-100 border-0 service-card tilt-card"><div class="card-body">
        <span class="service-icon" aria-hidden="true"></span>
        <h3 class="h5"><?= e($title) ?></h3><p class="mb-0"><?= e($text) ?></p>
      </div></article></div>
      <?php $si++; endforeach; ?>
    </div>
  </div>
</section>

<section id="packages" class="py-6 band band-alt">
  <div class="container">
    <div class="text-center mb-5">
      <?php if ($liveCount > 0): ?><span class="stock-counter mb-3"><span class="dot"></span> <?= $liveCount ?> bundle<?= $liveCount === 1 ? '' : 's' ?> ready to build now</span><?php endif; ?>
      <p class="section-kicker reveal">Ready builds</p>
      <h2 class="section-head reveal mb-2 mt-2">Starting points</h2>
      <p class="section-lead reveal mb-4 mx-auto">Every build is tuned to your budget and current stock. These are where most customers begin.</p>
    </div>

    <div class="row g-4 mb-5">
      <?php $n = 0; foreach ($categoryInfo as $cat => $items): $list = $bundlesByCat[$cat]; ?>
      <div class="col-md-4 reveal">
        <article class="card h-100 package package-selector tilt-card <?= $n === 1 ? 'package-featured' : '' ?>">
          <div class="card-body d-flex flex-column">
            <h3 class="h4"><?= e($cat) ?></h3>
            <p class="price"><?= $list ? 'From ' . e(peso(min(array_map('floatval', array_column($list, 'price'))))) : 'Built to order' ?></p>
            <ul class="list-unstyled flex-grow-1">
              <?php foreach ($items as $it): ?><li class="mb-2"><?= e($it) ?></li><?php endforeach; ?>
            </ul>
            <?php if ($list): ?>
            <button class="btn btn-accent view-bundles-btn" data-bs-toggle="modal" data-bs-target="#modal<?= e($cat) ?>">View <?= e($cat) ?> bundles (<?= count($list) ?>) →</button>
            <?php else: ?>
            <p class="small mb-2">No ready bundles in stock right now. We can still build one to order.</p>
            <a href="#quote" class="btn btn-accent quote-pick" data-category="<?= e($cat) ?>">Ask about <?= e($cat) ?> builds</a>
            <?php endif; ?>
          </div>
        </article>
      </div>
      <?php $n++; endforeach; ?>
    </div>
  </div>
</section>

<section id="planner" class="py-6 band">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <p class="section-kicker reveal">Interactive</p>
        <h2 class="section-head reveal">Plan your build</h2>
        <p class="section-lead reveal">Pick what the PC is for and slide to your budget. We will tell you what is realistic, then send it to us as a quote request.</p>
        <ul class="planner-points reveal">
          <li>Uses live stock minimums when bundles are available</li>
          <li>One click sends your plan into the quote form</li>
        </ul>
      </div>
      <div class="col-lg-7">
        <div class="planner" id="planner-widget" data-min='<?= e(json_encode($minimums)) ?>'>
          <p class="planner-label">Build planner</p>
          <label class="form-label fw-semibold" for="plUse">What is it for?</label>
          <select id="plUse" class="form-select mb-4"><?php foreach (array_keys($minimums) as $u): ?><option><?= e($u) ?></option><?php endforeach; ?></select>
          <label class="form-label fw-semibold" for="plBudget">Budget: <output id="plOut" class="text-accent">₱30,000</output></label>
          <input type="range" class="form-range mb-3" id="plBudget" min="10000" max="150000" step="5000" value="30000">
          <div id="plResult" class="planner-result" role="status" aria-live="polite"></div>
          <button type="button" id="plSend" class="btn btn-accent mt-3">Request this quote</button>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="process" class="py-6 band band-alt">
  <div class="container">
    <p class="section-kicker reveal">From message to handover</p>
    <h2 class="section-head reveal mb-4">How it works</h2>
    <ol class="row g-4 list-unstyled steps steps-rail">
      <?php foreach ($steps as $i => [$title, $text]): ?>
      <li class="col-md-6 col-lg-3 reveal reveal-delay-<?= min(3, $i + 1) ?>"><span class="step-n"><?= $i + 1 ?></span><h3 class="h5 mt-3"><?= e($title) ?></h3><p><?= e($text) ?></p></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section id="why-us" class="py-6 text-white bg-ink">
  <div class="container">
    <h2 class="section-head reveal mb-4">Why people come back</h2>
    <div class="row g-4">
      <div class="col-md-4"><div class="why why-red"><h3 class="h5">Warranty on every build</h3><p class="mb-0">If something fails, you deal with us, not a call center.</p></div></div>
      <div class="col-md-4"><div class="why why-green"><h3 class="h5">Local support</h3><p class="mb-0">Walk in with a problem. Our reputation here is built on follow-through.</p></div></div>
      <div class="col-md-4"><div class="why why-yellow"><h3 class="h5">Real stock, real prices</h3><p class="mb-0">We build from what is available now, so you know the cost and timeline upfront.</p></div></div>
    </div>
  </div>
</section>

<section id="gallery" class="py-6 band">
  <div class="container">
    <h2 class="section-head reveal mb-4">Recent builds</h2>
    <div class="row g-3">
      <?php for ($i = 1; $i <= 6; $i++): ?>
      <div class="col-6 col-lg-4 reveal reveal-delay-<?= (($i - 1) % 3) + 1 ?>"><button type="button" class="gallery-item" data-bs-toggle="modal" data-bs-target="#lightbox" data-alt="PC build <?= $i ?> assembled by DOi PC" aria-label="Open build photo <?= $i ?>">
        <img src="assets/img/build-<?= $i ?>.jpg" alt="PC build <?= $i ?> assembled by DOi PC" loading="lazy" onerror="this.onerror=null;this.src='<?= e(ph($i)) ?>'">
      </button></div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section id="testimonials" class="py-6 band band-alt">
  <div class="container">
    <div class="text-center mb-5">
      <?php if (!empty($site['rating_text'])): ?><div class="rating-stars mb-2">★★★★★<span class="rating-text"><?= e($site['rating_text']) ?></span></div><?php endif; ?>
      <h2 class="section-head reveal">What customers say</h2>
    </div>
    <div class="row g-4">
      <?php for ($i = 0; $i < 3; $i++): ?>
      <div class="col-md-4"><figure class="card h-100 p-4 border-0 mb-0 testimonial"><blockquote class="mb-3">Replace with a real customer quote, collected with their permission.</blockquote><figcaption class="small">Customer name, type of build</figcaption></figure></div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section id="faq" class="py-6 band">
  <div class="container" style="max-width: 760px;">
    <h2 class="section-head reveal mb-4">Questions, answered</h2>
    <div class="accordion" id="faqList">
      <?php foreach ($faqs as $i => [$q, $a]): ?>
      <div class="accordion-item">
        <h3 class="accordion-header"><button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>"><?= e($q) ?></button></h3>
        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faqList"><div class="accordion-body"><?= e($a) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="quote" class="py-6 band band-alt">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-6">
        <p class="section-kicker reveal">Free, no obligation</p>
        <h2 class="section-head reveal">Get a free build quote</h2>
        <p class="section-lead reveal">Tell us what you need. We reply with options based on current stock.</p>
        <p class="reply-cue reveal"><span class="reply-dot"></span> Usually reply within a few hours during store hours</p>
        <div id="formAlert" role="status" aria-live="polite"></div>
        <form id="quoteForm" action="backend/send-quote.php" method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="bundle_id" id="bundle_id" value="">
          <div class="d-none" aria-hidden="true"><label>Leave empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" required maxlength="80"></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" type="tel" required maxlength="30"></div>
            <div class="col-md-6 mb-3"><label class="form-label" for="email">Email (optional)</label><input class="form-control" id="email" name="email" type="email" maxlength="120"></div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label" for="usecase">What do you need?</label>
              <select class="form-select" id="usecase" name="usecase" required>
                <option value="" selected disabled>Choose one</option>
                <option>Office</option><option>Gaming</option><option>Workstation</option><option>Deep cleaning</option><option>Repair or upgrade</option><option>Bulk order</option>
              </select></div>
            <div class="col-md-6 mb-3"><label class="form-label" for="budget">Budget (₱)</label><input class="form-control" id="budget" name="budget" maxlength="30" placeholder="e.g. 35,000"></div>
          </div>
          <div class="mb-3"><label class="form-label" for="message">Details</label><textarea class="form-control" id="message" name="message" rows="4" maxlength="1500"></textarea></div>
          <button class="btn btn-accent btn-lg" type="submit">Send quote request</button>
        </form>
      </div>
      <div class="col-lg-6">
        <aside class="visit-panel reveal">
          <h3 class="h5">Visit or message us</h3>
          <dl class="visit-list">
            <div><dt>Location</dt><dd><?= e($site['address']) ?></dd></div>
            <div><dt>Hours</dt><dd><?= e($site['hours']) ?></dd></div>
            <div><dt>Phone</dt><dd><a href="tel:<?= e($tel) ?>"><?= e($site['phone']) ?></a></dd></div>
            <div><dt>Chat</dt><dd>
              <a href="<?= e($site['messenger']) ?>" target="_blank" rel="noopener">Messenger</a>
              <span class="visit-sep">·</span>
              <a href="https://wa.me/<?= e(ltrim($site['whatsapp'], '+')) ?>" target="_blank" rel="noopener">WhatsApp</a>
            </dd></div>
          </dl>
          <div class="ratio ratio-4x3 rounded overflow-hidden visit-map"><iframe src="<?= e($site['map_embed']) ?>" title="Map to DOi PC" loading="lazy" style="border:0"></iframe></div>
        </aside>
      </div>
    </div>
  </div>
</section>

<!-- Bundle modals live at page level (outside any <section>) so no parent animation/transform can trap them under the backdrop -->
  <?php foreach ($bundlesByCat as $cat => $list): if (!$list) continue; ?>
  <div class="modal fade" id="modal<?= e($cat) ?>" tabindex="-1" aria-labelledby="modal<?= e($cat) ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title" id="modal<?= e($cat) ?>Label"><?= e($cat) ?> Build Bundles</h3>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <?php foreach ($list as $b): $low = $b['available'] <= 2; ?>
            <div class="col-md-6 col-lg-4">
              <article class="card h-100 bundle-card">
                <?php if (!empty($b['image_url'])): ?><img src="<?= e($b['image_url']) ?>" alt="<?= e($b['name']) ?>" class="bundle-img" loading="lazy"><?php endif; ?>
                <div class="card-body d-flex flex-column">
                  <span class="stock-badge <?= $low ? 'low' : '' ?>"><?= $low ? 'Only ' . (int)$b['available'] . ' left' : 'In stock' ?></span>
                  <h4 class="h5 mb-2"><?= e($b['name']) ?></h4>
                  <p class="price mb-2"><?= e(peso($b['price'])) ?></p>
                  <?php if (!empty($b['description'])): ?><p class="small mb-2"><?= e($b['description']) ?></p><?php endif; ?>
                  <ul class="list-unstyled flex-grow-1 mb-3">
                    <?php foreach ($b['specs'] as $spec): ?><li class="mb-2"><small><?= e($spec) ?></small></li><?php endforeach; ?>
                  </ul>
                  <button class="btn btn-accent quote-pick" data-bs-dismiss="modal" data-bundle-id="<?= (int)$b['id'] ?>" data-package="<?= e($b['name']) ?>" data-price="<?= (int)$b['price'] ?>" data-category="<?= e($cat) ?>">Select &amp; Quote</button>
                </div>
              </article>
            </div>
            <?php endforeach; ?>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
