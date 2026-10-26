<?php
require __DIR__ . '/_boot.php';
requireAdmin();
$db = getDB();
$statuses = ['New', 'Contacted', 'Quoted', 'Converted', 'Closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_require();
  $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
  $st = $db->prepare('SELECT * FROM quote_requests WHERE id = ?'); $st->execute([$id]); $q = $st->fetch();
  if ($q && $a === 'status') {
    $new = $_POST['status'] ?? '';
    if (!in_array($new, $statuses, true)) { flash('danger', 'Invalid status.'); }
    elseif ($new === 'Converted' && $q['bundle_id'] && !(int)$q['stock_deducted']) {
      $err = deduct_bundle_stock($db, (int)$q['bundle_id']);
      if ($err) { flash('danger', 'Not marked as converted. ' . $err); }
      else {
        $db->prepare("UPDATE quote_requests SET status = 'Converted', stock_deducted = 1 WHERE id = ?")->execute([$id]);
        flash('success', 'Marked as converted and the bundle parts were deducted from stock.');
      }
    } else {
      $db->prepare('UPDATE quote_requests SET status = ? WHERE id = ?')->execute([$new, $id]);
      flash('success', 'Status updated.');
    }
  } elseif ($q && $a === 'delete') {
    $db->prepare('DELETE FROM quote_requests WHERE id = ?')->execute([$id]);
    flash('success', 'Quote request deleted.');
  }
  header('Location: quotes.php' . (!empty($_POST['f']) ? '?status=' . urlencode($_POST['f']) : '')); exit;
}

$f = $_GET['status'] ?? '';
$sql = 'SELECT * FROM quote_requests'; $par = [];
if (in_array($f, $statuses, true)) { $sql .= ' WHERE status = ?'; $par[] = $f; }
$st = $db->prepare($sql . ' ORDER BY created_at DESC LIMIT 200'); $st->execute($par);
$quotes = $st->fetchAll();
$pageTitle = 'Quotes'; $active = 'quotes';
require __DIR__ . '/_top.php';
?>
<h1 class="h3 mb-3">Quote requests</h1>
<div class="mb-3 d-flex flex-wrap gap-2">
  <a class="btn btn-sm <?= $f === '' ? 'btn-light' : 'btn-outline-light' ?>" href="quotes.php">All</a>
  <?php foreach ($statuses as $s): ?><a class="btn btn-sm <?= $f === $s ? 'btn-light' : 'btn-outline-light' ?>" href="quotes.php?status=<?= urlencode($s) ?>"><?= e($s) ?></a><?php endforeach; ?>
</div>
<div class="panel p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Received</th><th>Customer</th><th>Interested in</th><th>Message</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($quotes as $q): ?>
  <tr>
    <td class="text-nowrap"><?= e(date('M j, g:i A', strtotime($q['created_at']))) ?></td>
    <td><strong><?= e($q['name']) ?></strong><br><a href="tel:<?= e(preg_replace('/[^+\d]/', '', $q['phone'])) ?>"><?= e($q['phone']) ?></a>
      <?php if ($q['email']): ?><br><a href="mailto:<?= e($q['email']) ?>"><?= e($q['email']) ?></a><?php endif; ?></td>
    <td><?= e($q['bundle_name'] ?: $q['build_type']) ?><br><small class="text-muted-2"><?= $q['budget'] ? 'Budget: ₱' . e($q['budget']) : '' ?></small>
      <?php if ((int)$q['stock_deducted']): ?><br><span class="badge text-bg-success">Stock deducted</span><?php endif; ?></td>
    <td style="max-width:280px"><details><summary class="text-truncate" style="max-width:260px"><?= e(mb_substr((string)$q['message'], 0, 60) ?: '(no message)') ?></summary><p class="mb-0 mt-2" style="white-space:pre-wrap"><?= e($q['message']) ?></p></details></td>
    <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><input type="hidden" name="f" value="<?= e($f) ?>">
      <select class="form-select form-select-sm" name="status" aria-label="Status"><?php foreach ($statuses as $s): ?><option <?= $q['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-light">Save</button></form></td>
    <td><form method="post" onsubmit="return confirm('Delete this request?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><input type="hidden" name="f" value="<?= e($f) ?>"><button class="btn btn-sm btn-outline-danger" aria-label="Delete">&times;</button></form></td>
  </tr>
  <?php endforeach; if (!$quotes): ?><tr><td colspan="6" class="text-center text-muted-2 py-4">No requests here yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<p class="small text-muted-2 mt-2">Marking a request that came from a bundle as <strong>Converted</strong> subtracts that bundle's parts from inventory automatically (once).</p>
<?php require __DIR__ . '/_bottom.php'; ?>
