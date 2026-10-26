<?php
declare(strict_types=1);

/**
 * Bundles with live availability. A bundle can be built N times, where N is the lowest
 * (stock / quantity needed) across its parts. Public pages only get bundles with N >= 1.
 */
function fetch_bundles(PDO $db, bool $publicOnly = true, ?int $id = null): array {
  $sql = 'SELECT b.*, COUNT(bc.id) AS part_count,
            MIN(FLOOR(i.stock_quantity / bc.quantity)) AS available,
            SUM(CASE WHEN i.is_active = 0 THEN 1 ELSE 0 END) AS inactive_parts,
            SUM(i.price * bc.quantity) AS parts_cost
          FROM bundles b
          LEFT JOIN bundle_components bc ON bc.bundle_id = b.id
          LEFT JOIN hardware_items i ON i.id = bc.hardware_item_id';
  $where = []; $params = [];
  if ($id !== null) { $where[] = 'b.id = ?'; $params[] = $id; }
  if ($publicOnly) { $where[] = 'b.is_active = 1'; }
  if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
  $sql .= ' GROUP BY b.id';
  if ($publicOnly) { $sql .= ' HAVING part_count > 0 AND available >= 1 AND inactive_parts = 0'; }
  $sql .= " ORDER BY FIELD(b.category,'Office','Gaming','Workstation'), b.sort_order, b.price";
  $st = $db->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();
  foreach ($rows as &$r) {
    $r['available'] = (int)$r['available'];
    $r['part_count'] = (int)$r['part_count'];
    $r['inactive_parts'] = (int)$r['inactive_parts'];
    $r['parts_cost'] = (float)$r['parts_cost'];
  }
  unset($r);
  return attach_components($db, $rows);
}

/** Adds ['components'] (raw rows) and ['specs'] (display lines) to each bundle. */
function attach_components(PDO $db, array $bundles): array {
  if (!$bundles) { return $bundles; }
  $ids = array_column($bundles, 'id');
  $in = implode(',', array_fill(0, count($ids), '?'));
  $st = $db->prepare("SELECT bc.bundle_id, bc.quantity, i.id AS item_id, i.brand, i.model, i.price,
                             i.stock_quantity, i.is_active, c.name AS cat
                      FROM bundle_components bc
                      JOIN hardware_items i ON i.id = bc.hardware_item_id
                      JOIN hardware_categories c ON c.id = i.category_id
                      WHERE bc.bundle_id IN ($in) ORDER BY c.display_order, i.model");
  $st->execute($ids);
  $by = [];
  foreach ($st->fetchAll() as $row) { $by[$row['bundle_id']][] = $row; }
  foreach ($bundles as &$b) {
    $comps = $by[$b['id']] ?? [];
    $specs = [];
    foreach ($comps as $c) {
      $specs[] = $c['cat'] . ': ' . ((int)$c['quantity'] > 1 ? $c['quantity'] . '× ' : '') . $c['brand'] . ' ' . $c['model'];
    }
    foreach (preg_split('/\R/', (string)$b['extra_specs']) as $line) {
      $line = trim($line);
      if ($line !== '') { $specs[] = $line; }
    }
    $b['components'] = $comps;
    $b['specs'] = $specs;
  }
  unset($b);
  return $bundles;
}

/** [label, bootstrap color, isLiveOnWebsite] for the admin lists. */
function bundle_status(array $b): array {
  if (!(int)$b['is_active']) { return ['Hidden', 'secondary', false]; }
  if ($b['part_count'] === 0) { return ['No parts linked', 'warning', false]; }
  if ($b['inactive_parts'] > 0) { return ['Part disabled', 'warning', false]; }
  if ($b['available'] < 1) { return ['Out of stock', 'danger', false]; }
  if ($b['available'] <= 2) { return ['Low stock', 'warning', true]; }
  return ['Live', 'success', true];
}

/** Subtracts one bundle's parts from inventory. Returns an error message, or null on success. */
function deduct_bundle_stock(PDO $db, int $bundleId): ?string {
  $db->beginTransaction();
  try {
    $st = $db->prepare('SELECT bc.quantity, i.id, i.brand, i.model, i.stock_quantity
                        FROM bundle_components bc JOIN hardware_items i ON i.id = bc.hardware_item_id
                        WHERE bc.bundle_id = ? FOR UPDATE');
    $st->execute([$bundleId]);
    $rows = $st->fetchAll();
    if (!$rows) { $db->rollBack(); return 'That bundle has no parts linked, so stock cannot be deducted.'; }
    foreach ($rows as $r) {
      if ((int)$r['stock_quantity'] < (int)$r['quantity']) {
        $db->rollBack();
        return 'Not enough stock for ' . $r['brand'] . ' ' . $r['model'] . '.';
      }
    }
    $up = $db->prepare('UPDATE hardware_items SET stock_quantity = stock_quantity - ? WHERE id = ?');
    foreach ($rows as $r) { $up->execute([$r['quantity'], $r['id']]); }
    $db->commit();
    return null;
  } catch (Throwable $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    throw $e;
  }
}
