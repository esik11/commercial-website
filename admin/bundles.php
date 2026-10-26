<?php
require __DIR__ . '/_boot.php';
requireAdmin();
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
  if ($a === 'toggle' && $id) {
    $db->prepare('UPDATE bundles SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    flash('success', 'Bundle visibility changed.');
  } elseif ($a === 'delete' && $id) {
    $st = $db->prepare('SELECT image_url FROM bundles WHERE id = ?'); $st->execute([$id]); $img = $st->fetchColumn();
    $db->prepare('DELETE FROM bundles WHERE id = ?')->execute([$id]);
    if ($img && str_starts_with($img, 'assets/uploads/bundles/')) { @unlink(__DIR__ . '/../' . $img); }
    flash('success', 'Bundle deleted.');
  }
  header('Location: bundles.php'); exit;
}
$bundles = fetch_bundles($db, false);
$pageTitle = 'Bundles'; $active = 'bundles';
require __DIR__ . '/_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Bundles</h1><a class="btn btn-accent" href="bundle_edit.php">Add bundle</a>
</div>
<p class="text-muted-2">A bundle appears on the website only while it is switched on <em>and</em> every linked part is active and in stock.</p>
<div class="panel p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Bundle</th><th>Price</th><th>Parts cost</th><th>Margin</th><th>Can build</th><th>Website</th><th class="text-end">Actions</th></tr></thead><tbody>
  <?php foreach ($bundles as $b): [$label, $color] = bundle_status($b); $margin = (float)$b['price'] - $b['parts_cost']; ?>
  <tr>
    <td><strong><?= e($b['name']) ?></strong><br><small class="text-muted-2"><?= e($b['category']) ?> &middot; <?= (int)$b['part_count'] ?> parts</small></td>
    <td><?= e(peso($b['price'])) ?></td>
    <td><?= e(peso($b['parts_cost'])) ?></td>
    <td class="<?= $margin < 0 ? 'text-danger' : '' ?>"><?= e(peso($margin)) ?></td>
    <td><?= (int)$b['available'] ?></td>
    <td><span class="badge text-bg-<?= e($color) ?>"><?= e($label) ?></span></td>
    <td class="text-end text-nowrap">
      <a class="btn btn-sm btn-outline-light" href="bundle_edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
      <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-warning"><?= (int)$b['is_active'] ? 'Hide' : 'Show' ?></button></form>
      <form method="post" class="d-inline" onsubmit="return confirm('Delete this bundle?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
    </td>
  </tr>
  <?php endforeach; if (!$bundles): ?><tr><td colspan="7" class="text-center text-muted-2 py-4">No bundles yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/_bottom.php'; ?>
