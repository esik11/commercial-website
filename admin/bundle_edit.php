<?php
require __DIR__ . '/_boot.php';
requireAdmin();
$db = getDB();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$bundle = null;
if ($id) {
  $r = fetch_bundles($db, false, $id); $bundle = $r[0] ?? null;
  if (!$bundle) { flash('danger', 'Bundle not found.'); header('Location: bundles.php'); exit; }
}
$items = $db->query('SELECT i.*, c.name AS cat FROM hardware_items i JOIN hardware_categories c ON c.id = i.category_id
                     WHERE i.is_active = 1 ORDER BY c.display_order, i.brand, i.model')->fetchAll();
$itemIds = array_map('intval', array_column($items, 'id'));
$categories = ['Office', 'Gaming', 'Workstation'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $category = $_POST['category'] ?? ''; $name = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
  $price = $_POST['price'] ?? ''; $desc = trim($_POST['description'] ?? ''); $extra = trim($_POST['extra_specs'] ?? '');
  $sort = (int)($_POST['sort_order'] ?? 0); $active = isset($_POST['is_active']) ? 1 : 0;

  if (!in_array($category, $categories, true)) { $errors[] = 'Choose a category.'; }
  if ($name === '') { $errors[] = 'Enter a bundle name.'; }
  if (!is_numeric($price) || (float)$price < 0) { $errors[] = 'Enter a valid price.'; }

  $map = [];
  foreach ((array)($_POST['comp_item'] ?? []) as $k => $iid) {
    $iid = (int)$iid; $q = max(1, (int)(((array)($_POST['comp_qty'] ?? []))[$k] ?? 1));
    if ($iid && in_array($iid, $itemIds, true)) { $map[$iid] = ($map[$iid] ?? 0) + $q; }
  }
  if (!$map) { $errors[] = 'Add at least one part so the website can check stock for this bundle.'; }

  $imageUrl = $bundle['image_url'] ?? null; $oldImage = null;
  if (isset($_POST['remove_image']) && $imageUrl) { $oldImage = $imageUrl; $imageUrl = null; }
  if (!empty($_FILES['image']['name'])) {
    $f = $_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) { $errors[] = 'The image upload failed. Try a smaller file.'; }
    elseif ($f['size'] > 2 * 1024 * 1024) { $errors[] = 'Image must be under 2 MB.'; }
    else {
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
      $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
      if (!$ext) { $errors[] = 'Image must be a JPG, PNG or WebP file.'; }
      else {
        $fname = bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($f['tmp_name'], __DIR__ . '/../assets/uploads/bundles/' . $fname)) {
          $oldImage = $imageUrl ?: $oldImage; $imageUrl = 'assets/uploads/bundles/' . $fname;
        } else { $errors[] = 'Could not save the image. Check folder permissions.'; }
      }
    }
  }

  if (!$errors) {
    $db->beginTransaction();
    try {
      if ($id) {
        $db->prepare('UPDATE bundles SET category=?, name=?, price=?, description=?, extra_specs=?, image_url=?, is_active=?, sort_order=? WHERE id=?')
           ->execute([$category, $name, $price, $desc, $extra, $imageUrl, $active, $sort, $id]);
      } else {
        $db->prepare('INSERT INTO bundles (category, name, price, description, extra_specs, image_url, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?)')
           ->execute([$category, $name, $price, $desc, $extra, $imageUrl, $active, $sort]);
        $id = (int)$db->lastInsertId();
      }
      $db->prepare('DELETE FROM bundle_components WHERE bundle_id = ?')->execute([$id]);
      $ins = $db->prepare('INSERT INTO bundle_components (bundle_id, hardware_item_id, quantity) VALUES (?,?,?)');
      foreach ($map as $iid => $q) { $ins->execute([$id, $iid, $q]); }
      $db->commit();
    } catch (Throwable $ex) { if ($db->inTransaction()) { $db->rollBack(); } throw $ex; }
    if ($oldImage && str_starts_with($oldImage, 'assets/uploads/bundles/')) { @unlink(__DIR__ . '/../' . $oldImage); }
    flash('success', 'Bundle saved.'); header('Location: bundles.php'); exit;
  }
}

// Values to show in the form: submitted values on error, saved values otherwise
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$v = fn(string $k, $def = '') => $isPost ? ($_POST[$k] ?? $def) : ($bundle[$k] ?? $def);
$rows = [];
if ($isPost) {
  foreach ((array)($_POST['comp_item'] ?? []) as $k => $iid) { $rows[] = [(int)$iid, max(1, (int)(((array)($_POST['comp_qty'] ?? []))[$k] ?? 1))]; }
} elseif ($bundle) {
  foreach ($bundle['components'] as $c) { $rows[] = [(int)$c['item_id'], (int)$c['quantity']]; }
}
if (!$rows) { $rows[] = [0, 1]; }

$pageTitle = $bundle ? 'Edit bundle' : 'Add bundle'; $active = 'bundles';
require __DIR__ . '/_top.php';
$optHtml = '<option value="0">Choose a part...</option>';
$lastCat = '';
foreach ($items as $it) {
  if ($it['cat'] !== $lastCat) { if ($lastCat !== '') { $optHtml .= '</optgroup>'; } $optHtml .= '<optgroup label="' . e($it['cat']) . '">'; $lastCat = $it['cat']; }
  $optHtml .= '<option value="' . (int)$it['id'] . '" data-price="' . e($it['price']) . '" data-stock="' . (int)$it['stock_quantity'] . '">' . e($it['brand'] . ' ' . $it['model']) . ' (' . (int)$it['stock_quantity'] . ' in stock)</option>';
}
if ($lastCat !== '') { $optHtml .= '</optgroup>'; }
?>
<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="row g-4"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="col-lg-7"><div class="panel">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label" for="category">Category</label>
        <select class="form-select" id="category" name="category" required><?php foreach ($categories as $c): ?><option <?= $v('category') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label" for="name">Bundle name</label><input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($v('name')) ?>"></div>
      <div class="col-md-4"><label class="form-label" for="price">Selling price (₱)</label><input class="form-control" id="price" name="price" type="number" min="0" step="1" required value="<?= e($v('price')) ?>"></div>
      <div class="col-md-4"><label class="form-label" for="sort_order">Display order</label><input class="form-control" id="sort_order" name="sort_order" type="number" value="<?= e($v('sort_order', 0)) ?>"></div>
      <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= $isPost ? (isset($_POST['is_active']) ? 'checked' : '') : (!$bundle || (int)$bundle['is_active'] ? 'checked' : '') ?>><label class="form-check-label" for="is_active">Show on website</label></div></div>
      <div class="col-12"><label class="form-label" for="description">Short description</label><input class="form-control" id="description" name="description" maxlength="250" value="<?= e($v('description')) ?>"></div>
      <div class="col-12"><label class="form-label" for="extra_specs">Extra specs, one per line</label>
        <textarea class="form-control" id="extra_specs" name="extra_specs" rows="4" placeholder="Windows 11 Home&#10;1-year warranty"><?= e($v('extra_specs')) ?></textarea>
        <div class="form-text text-muted-2">For things that are not tracked as stock, like the operating system or warranty. Parts below are listed automatically.</div></div>
      <div class="col-12"><label class="form-label" for="image">Photo (JPG, PNG or WebP, max 2 MB)</label><input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($bundle['image_url'])): ?><div class="mt-2 d-flex align-items-center gap-3"><img src="../<?= e($bundle['image_url']) ?>" alt="" style="height:64px;border-radius:.4rem"><div class="form-check"><input class="form-check-input" type="checkbox" id="rm" name="remove_image"><label class="form-check-label" for="rm">Remove photo</label></div></div><?php endif; ?></div>
    </div>
  </div></div>
  <div class="col-lg-5"><div class="panel">
    <h2 class="h5">Parts in this bundle</h2>
    <p class="small text-muted-2">Pick the parts from your inventory. The website shows this bundle only while all of them are in stock.</p>
    <div id="rows"><?php foreach ($rows as [$iid, $qty]): ?>
      <div class="d-flex gap-2 mb-2 comp-row">
        <select class="form-select comp-item" name="comp_item[]" aria-label="Part"><?= str_replace('value="' . $iid . '"', 'value="' . $iid . '" selected', $optHtml) ?></select>
        <input class="form-control qty-input comp-qty" name="comp_qty[]" type="number" min="1" value="<?= (int)$qty ?>" aria-label="Quantity">
        <button type="button" class="btn btn-outline-danger rm-row" aria-label="Remove part">&times;</button>
      </div>
    <?php endforeach; ?></div>
    <button type="button" class="btn btn-sm btn-outline-light" id="addRow">Add another part</button>
    <div class="preview-box mt-3" id="preview" aria-live="polite"></div>
  </div></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-accent">Save bundle</button><a class="btn btn-outline-light" href="bundles.php">Cancel</a></div>
</form>
<template id="rowTpl"><div class="d-flex gap-2 mb-2 comp-row">
  <select class="form-select comp-item" name="comp_item[]" aria-label="Part"><?= $optHtml ?></select>
  <input class="form-control qty-input comp-qty" name="comp_qty[]" type="number" min="1" value="1" aria-label="Quantity">
  <button type="button" class="btn btn-outline-danger rm-row" aria-label="Remove part">&times;</button>
</div></template>
<script>
(function () {
  const rows = document.getElementById('rows'), prev = document.getElementById('preview'), price = document.getElementById('price');
  const peso = n => '₱' + Math.round(n).toLocaleString('en-PH');
  function update() {
    let cost = 0, can = Infinity, any = false;
    rows.querySelectorAll('.comp-row').forEach(r => {
      const o = r.querySelector('.comp-item').selectedOptions[0], q = Math.max(1, +r.querySelector('.comp-qty').value || 1);
      if (!o || !+o.value) return;
      any = true; cost += (+o.dataset.price) * q; can = Math.min(can, Math.floor((+o.dataset.stock) / q));
    });
    if (!any) { prev.textContent = 'Add a part to see the cost and how many you can build.'; return; }
    const margin = (+price.value || 0) - cost;
    prev.innerHTML = 'Parts cost <strong>' + peso(cost) + '</strong>. You can build <strong>' + can + '</strong> right now.' +
      (price.value ? ' Margin: <strong class="' + (margin < 0 ? 'text-danger' : '') + '">' + peso(margin) + '</strong>.' : '');
  }
  document.getElementById('addRow').addEventListener('click', () => { rows.appendChild(document.getElementById('rowTpl').content.cloneNode(true)); update(); });
  rows.addEventListener('click', e => { if (e.target.closest('.rm-row')) { const r = e.target.closest('.comp-row'); if (rows.children.length > 1) r.remove(); update(); } });
  rows.addEventListener('input', update); price.addEventListener('input', update); update();
})();
</script>
<?php require __DIR__ . '/_bottom.php'; ?>
