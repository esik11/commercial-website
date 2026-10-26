<?php
require __DIR__ . '/_boot.php';
requireAdmin();
$db = getDB();
$cats = $db->query('SELECT * FROM hardware_categories ORDER BY display_order')->fetchAll();
$catIds = array_map('intval', array_column($cats, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $a = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  if ($a === 'save') {
    $cat = (int)($_POST['category_id'] ?? 0);
    $brand = mb_substr(trim($_POST['brand'] ?? ''), 0, 100);
    $model = mb_substr(trim($_POST['model'] ?? ''), 0, 200);
    $price = $_POST['price'] ?? ''; $stock = (string)($_POST['stock_quantity'] ?? '');
    $spec = mb_substr(trim($_POST['specifications'] ?? ''), 0, 1000);
    if (!in_array($cat, $catIds, true) || $brand === '' || $model === '' || !is_numeric($price) || (float)$price < 0 || !ctype_digit($stock)) {
      flash('danger', 'Please fill in category, brand, model, a valid price and a whole-number stock.');
      header('Location: inventory.php' . ($id ? '?edit=' . $id : '')); exit;
    }
    if ($id) {
      $db->prepare('UPDATE hardware_items SET category_id=?, brand=?, model=?, price=?, stock_quantity=?, specifications=? WHERE id=?')
         ->execute([$cat, $brand, $model, $price, (int)$stock, $spec, $id]);
    } else {
      $db->prepare('INSERT INTO hardware_items (category_id, brand, model, price, stock_quantity, specifications) VALUES (?,?,?,?,?,?)')
         ->execute([$cat, $brand, $model, $price, (int)$stock, $spec]);
    }
    flash('success', 'Item saved.');
  } elseif ($a === 'stock' && $id) {
    $s = (string)($_POST['stock_quantity'] ?? '');
    if (ctype_digit($s)) { $db->prepare('UPDATE hardware_items SET stock_quantity = ? WHERE id = ?')->execute([(int)$s, $id]); flash('success', 'Stock updated.'); }
    else { flash('danger', 'Stock must be a whole number.'); }
  } elseif ($a === 'toggle' && $id) {
    $db->prepare('UPDATE hardware_items SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    flash('success', 'Item visibility changed.');
  } elseif ($a === 'delete' && $id) {
    try { $db->prepare('DELETE FROM hardware_items WHERE id = ?')->execute([$id]); flash('success', 'Item deleted.'); }
    catch (PDOException $ex) { flash('danger', 'This item is used in a bundle. Remove it from the bundle first, or disable the item instead.'); }
  }
  $qs = http_build_query(array_filter(['cat' => $_POST['cat'] ?? '', 'q' => $_POST['q'] ?? '']));
  header('Location: inventory.php' . ($qs ? '?' . $qs : '')); exit;
}

$fCat = (int)($_GET['cat'] ?? 0); $fQ = trim($_GET['q'] ?? '');
$sql = 'SELECT i.*, c.name AS cat FROM hardware_items i JOIN hardware_categories c ON c.id = i.category_id WHERE 1=1'; $par = [];
if ($fCat) { $sql .= ' AND i.category_id = ?'; $par[] = $fCat; }
if ($fQ !== '') { $sql .= ' AND (i.brand LIKE ? OR i.model LIKE ?)'; $par[] = "%$fQ%"; $par[] = "%$fQ%"; }
$st = $db->prepare($sql . ' ORDER BY c.display_order, i.brand, i.model LIMIT 300'); $st->execute($par);
$items = $st->fetchAll();

$edit = null;
if (isset($_GET['edit'])) { $st = $db->prepare('SELECT * FROM hardware_items WHERE id = ?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$pageTitle = 'Inventory'; $active = 'inventory';
require __DIR__ . '/_top.php';
$hid = '<input type="hidden" name="cat" value="' . e($fCat ?: '') . '"><input type="hidden" name="q" value="' . e($fQ) . '">';
?>
<h1 class="h3 mb-3">Inventory</h1>
<div class="panel mb-4">
  <h2 class="h5"><?= $edit ? 'Edit item' : 'Add item' ?></h2>
  <form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="col-md-3"><label class="form-label" for="category_id">Category</label>
      <select class="form-select" id="category_id" name="category_id" required><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($edit['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label" for="brand">Brand</label><input class="form-control" id="brand" name="brand" required maxlength="100" value="<?= e($edit['brand'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="model">Model</label><input class="form-control" id="model" name="model" required maxlength="200" value="<?= e($edit['model'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label" for="price">Price (₱)</label><input class="form-control" id="price" name="price" type="number" min="0" step="0.01" required value="<?= e($edit['price'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label" for="stock_quantity">Stock</label><input class="form-control" id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" required value="<?= e($edit['stock_quantity'] ?? 0) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="specifications">Notes / specs (optional)</label><input class="form-control" id="specifications" name="specifications" maxlength="1000" value="<?= e($edit['specifications'] ?? '') ?>"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-accent"><?= $edit ? 'Save changes' : 'Add item' ?></button><?php if ($edit): ?><a class="btn btn-outline-light" href="inventory.php">Cancel</a><?php endif; ?></div>
  </form>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-3"><select class="form-select" name="cat" onchange="this.form.submit()"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $fCat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-5"><input class="form-control" name="q" placeholder="Search brand or model" value="<?= e($fQ) ?>"></div>
  <div class="col-auto"><button class="btn btn-outline-light">Search</button></div>
</form>

<div class="panel p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Category</th><th>Item</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($items as $i): ?>
  <tr class="<?= (int)$i['is_active'] ? '' : 'opacity-50' ?>">
    <td><?= e($i['cat']) ?></td>
    <td><strong><?= e($i['brand']) ?></strong> <?= e($i['model']) ?></td>
    <td><?= e(peso($i['price'])) ?></td>
    <td><form method="post" class="d-flex gap-1"><?= csrf_field() . $hid ?><input type="hidden" name="action" value="stock"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
      <input class="form-control form-control-sm stock-input" name="stock_quantity" type="number" min="0" value="<?= (int)$i['stock_quantity'] ?>" aria-label="Stock for <?= e($i['model']) ?>"><button class="btn btn-sm btn-outline-light">Set</button></form></td>
    <td><?php if (!(int)$i['is_active']): ?><span class="badge text-bg-secondary">Disabled</span><?php elseif ((int)$i['stock_quantity'] === 0): ?><span class="badge text-bg-danger">Out</span><?php elseif ((int)$i['stock_quantity'] <= 3): ?><span class="badge text-bg-warning">Low</span><?php else: ?><span class="badge text-bg-success">OK</span><?php endif; ?></td>
    <td class="text-end text-nowrap">
      <a class="btn btn-sm btn-outline-light" href="inventory.php?edit=<?= (int)$i['id'] ?>">Edit</a>
      <form method="post" class="d-inline"><?= csrf_field() . $hid ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>"><button class="btn btn-sm btn-outline-warning"><?= (int)$i['is_active'] ? 'Disable' : 'Enable' ?></button></form>
      <form method="post" class="d-inline" onsubmit="return confirm('Delete this item permanently?')"><?= csrf_field() . $hid ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
    </td>
  </tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="6" class="text-center text-muted-2 py-4">No items found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<p class="small text-muted-2 mt-2">Tip: disabling an item hides every bundle that uses it. Setting stock to 0 does the same until you restock.</p>
<?php require __DIR__ . '/_bottom.php'; ?>
