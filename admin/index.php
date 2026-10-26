<?php
require __DIR__ . '/_boot.php';
requireAdmin();
$db = getDB();
$all = fetch_bundles($db, false);
$live = 0; $attention = [];
foreach ($all as $b) {
  [$label, , $isLive] = bundle_status($b);
  if ($isLive) { $live++; } elseif ((int)$b['is_active']) { $attention[] = [$b, $label]; }
}
$lowItems = $db->query('SELECT i.*, c.name AS cat FROM hardware_items i JOIN hardware_categories c ON c.id = i.category_id
                        WHERE i.is_active = 1 AND i.stock_quantity <= 3 ORDER BY i.stock_quantity, i.brand LIMIT 8')->fetchAll();
$newQuotes = (int)$db->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'New'")->fetchColumn();
$recent = $db->query('SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5')->fetchAll();
$pageTitle = 'Dashboard'; $active = 'index';
require __DIR__ . '/_top.php';
?>
<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-tile green"><div class="n"><?= $live ?></div><div class="text-muted-2">Bundles live on the website</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile red"><div class="n"><?= count($attention) ?></div><div class="text-muted-2">Active but not showing</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile yellow"><div class="n"><?= count($lowItems) ?></div><div class="text-muted-2">Parts with 3 or fewer left</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="n"><?= $newQuotes ?></div><div class="text-muted-2">New quote requests</div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-6"><div class="panel h-100">
    <h2 class="h5">Bundles that need attention</h2>
    <?php if (!$attention): ?><p class="text-muted-2 mb-0">Every active bundle is available to customers.</p><?php else: ?>
    <ul class="list-unstyled mb-0"><?php foreach ($attention as [$b, $label]): ?>
      <li class="d-flex justify-content-between border-bottom border-secondary-subtle py-2"><span><?= e($b['name']) ?> <small class="text-muted-2">(<?= e($b['category']) ?>)</small></span><span class="badge text-bg-danger"><?= e($label) ?></span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </div></div>
  <div class="col-lg-6"><div class="panel h-100">
    <h2 class="h5">Restock soon</h2>
    <?php if (!$lowItems): ?><p class="text-muted-2 mb-0">No parts are running low.</p><?php else: ?>
    <ul class="list-unstyled mb-0"><?php foreach ($lowItems as $i): ?>
      <li class="d-flex justify-content-between border-bottom border-secondary-subtle py-2"><span><?= e($i['brand'] . ' ' . $i['model']) ?> <small class="text-muted-2"><?= e($i['cat']) ?></small></span><span class="badge text-bg-<?= (int)$i['stock_quantity'] === 0 ? 'danger' : 'warning' ?>"><?= (int)$i['stock_quantity'] ?> left</span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </div></div>
  <div class="col-12"><div class="panel">
    <div class="d-flex justify-content-between"><h2 class="h5">Latest quote requests</h2><a href="quotes.php">View all</a></div>
    <?php if (!$recent): ?><p class="text-muted-2 mb-0">No requests yet.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><tbody>
      <?php foreach ($recent as $q): ?><tr><td><?= e(date('M j, g:i A', strtotime($q['created_at']))) ?></td><td><?= e($q['name']) ?></td><td><?= e($q['bundle_name'] ?: $q['build_type']) ?></td><td><span class="badge text-bg-secondary"><?= e($q['status']) ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></div>
</div>
<?php require __DIR__ . '/_bottom.php'; ?>
