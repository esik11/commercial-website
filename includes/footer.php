</main>
<footer class="site-footer py-5">
  <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
    <div>
      <strong><?= htmlspecialchars($site['name']) ?></strong>
      <p class="mb-0 small"><?= htmlspecialchars($site['address']) ?></p>
    </div>
    <div class="small">
      <a href="tel:<?= preg_replace('/[^+\d]/', '', $site['phone']) ?>"><?= htmlspecialchars($site['phone']) ?></a><br>
      <a href="<?= htmlspecialchars($site['messenger']) ?>" target="_blank" rel="noopener">Message us on Messenger</a>
    </div>
    <p class="small mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. All rights reserved.</p>
  </div>
</footer>
<a class="fab-messenger" href="<?= htmlspecialchars($site['messenger']) ?>" target="_blank" rel="noopener" aria-label="Chat with us on Messenger">💬 Chat</a>
<a href="#top" class="fab-top" id="backToTop" aria-label="Back to top">↑</a>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="lightbox" tabindex="-1" aria-labelledby="lightboxLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-header border-0">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" alt="" id="lightboxImg" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=20261028c"></script>
</body>
</html>
