<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$pdo  = db();
$user = current_user();
$msg  = '';
$err  = '';
$uploadDir = __DIR__ . '/../uploads/';

// ── Handle actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $eventId  = ($_POST['event_id'] ?? '') !== '' ? (int)$_POST['event_id'] : null;
        $desc     = trim($_POST['description'] ?? '');
        $qty      = (float)($_POST['qty'] ?? 1);
        $price    = (float)($_POST['unit_price'] ?? 0);
        $date     = $_POST['date'] ?? date('Y-m-d');
        $notes    = trim($_POST['notes'] ?? '');
        $reimb    = isset($_POST['is_reimbursed']) ? 1 : 0;
        $reimbBy  = trim($_POST['reimbursed_by'] ?? '');
        $reimbDt  = $_POST['reimbursed_date'] ?? null;

        if ($desc === '') { $err = 'Description is required.'; }
        elseif ($price <= 0) { $err = 'Unit price must be greater than 0.'; }
        else {
            if ($action === 'create') {
                $pdo->prepare("INSERT INTO finance_expenses (event_id,description,quantity,unit_price,date,notes,is_reimbursed,reimbursed_by,reimbursed_date)
                    VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$eventId, $desc, $qty, $price, $date, $notes, $reimb, $reimbBy ?: null, $reimbDt ?: null]);
                $id = (int)$pdo->lastInsertId();
                $msg = 'Expense added.';
            } else {
                $pdo->prepare("UPDATE finance_expenses SET event_id=?,description=?,quantity=?,unit_price=?,date=?,notes=?,is_reimbursed=?,reimbursed_by=?,reimbursed_date=?,updated_at=NOW() WHERE id=?")
                    ->execute([$eventId, $desc, $qty, $price, $date, $notes, $reimb, $reimbBy ?: null, $reimbDt ?: null, $id]);
                log_history('finance_expenses', $id, 'edit', '', $desc, $user['username']);
                $msg = 'Expense updated.';
            }
            // Handle file uploads
            if (!empty($_FILES['receipts']['name'][0])) {
                foreach ($_FILES['receipts']['tmp_name'] as $k => $tmp) {
                    if (!is_uploaded_file($tmp)) continue;
                    $orig  = basename($_FILES['receipts']['name'][$k]);
                    $mime  = mime_content_type($tmp);
                    $size  = $_FILES['receipts']['size'][$k];
                    $fname = uniqid('rcpt_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
                    if (move_uploaded_file($tmp, $uploadDir . $fname)) {
                        $pdo->prepare("INSERT INTO finance_attachments (expense_id,filename,original,mime_type,file_size) VALUES (?,?,?,?,?)")
                            ->execute([$id, $fname, $orig, $mime, $size]);
                    }
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE finance_expenses SET is_deleted=1 WHERE id=?")->execute([$id]);
            log_history('finance_expenses', $id, 'soft_delete', '0', '1', $user['username']);
            $msg = 'Expense deleted (soft).';
        }
    }

    if ($action === 'delete_attach') {
        $aid = (int)($_POST['attach_id'] ?? 0);
        if ($aid) {
            $row = $pdo->prepare("SELECT filename FROM finance_attachments WHERE id=?")->execute([$aid])->fetchAll();
            $row = $pdo->prepare("SELECT filename FROM finance_attachments WHERE id=?");
            $row->execute([$aid]);
            $att = $row->fetch();
            if ($att && file_exists($uploadDir . $att['filename'])) unlink($uploadDir . $att['filename']);
            $pdo->prepare("DELETE FROM finance_attachments WHERE id=?")->execute([$aid]);
            $msg = 'Attachment removed.';
        }
    }
}

// ── Filters ──
$filterEvent = (int)($_GET['event_id'] ?? 0);
$filterDate  = trim($_GET['date'] ?? '');
$filterText  = trim($_GET['q'] ?? '');
$filterReim  = $_GET['reimbursed'] ?? '';

$where = ['x.is_deleted = 0'];
$params = [];
if ($filterEvent) { $where[] = 'x.event_id = ?'; $params[] = $filterEvent; }
if ($filterDate)  { $where[] = 'x.date = ?';      $params[] = $filterDate; }
if ($filterText)  { $where[] = '(x.description LIKE ? OR x.notes LIKE ?)'; $params[] = "%$filterText%"; $params[] = "%$filterText%"; }
if ($filterReim !== '') { $where[] = 'x.is_reimbursed = ?'; $params[] = (int)$filterReim; }

$whereStr = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT x.*, e.name AS event_name, e.color AS event_color
    FROM finance_expenses x
    LEFT JOIN finance_events e ON e.id = x.event_id
    {$whereStr}
    ORDER BY x.date DESC, x.id DESC
");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$total = array_sum(array_column($expenses, 'amount'));

$eventsAll = $pdo->query("SELECT id, name, color FROM finance_events WHERE is_archived=0 ORDER BY name")->fetchAll();

layout_head('Expenses');
layout_sidebar('expenses');
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">⚠️ <?= h($err) ?></div><?php endif; ?>

<!-- Filter bar -->
<form method="GET" class="filter-bar">
  <input class="form-control" name="q" placeholder="🔍 Search description…" value="<?= h($filterText) ?>">
  <select class="form-control" name="event_id" data-autosubmit>
    <option value="">All Events</option>
    <?php foreach ($eventsAll as $ev): ?>
      <option value="<?= $ev['id'] ?>" <?= $filterEvent==$ev['id']?'selected':'' ?>><?= h($ev['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input class="form-control" type="date" name="date" value="<?= h($filterDate) ?>">
  <select class="form-control" name="reimbursed" data-autosubmit>
    <option value="">All</option>
    <option value="1" <?= $filterReim==='1'?'selected':'' ?>>Reimbursed</option>
    <option value="0" <?= $filterReim==='0'?'selected':'' ?>>Not Reimbursed</option>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-secondary" href="?">Clear</a>
</form>

<!-- Header row -->
<div class="flex justify-between items-center mb-16" style="margin-bottom:16px;flex-wrap:wrap;gap:8px;">
  <span style="font-size:13px;color:var(--text-muted);">
    <?= count($expenses) ?> record(s) &nbsp;·&nbsp; Total: <strong style="color:var(--danger);"><?= format_money($total) ?></strong>
  </span>
  <button class="btn btn-primary" onclick="openModal('modalCreate')">+ Add Expense</button>
</div>

<!-- Table -->
<div class="table-wrap">
  <?php if (empty($expenses)): ?>
    <div class="empty-state"><div class="empty-icon">🧾</div><h3>No expenses found</h3><p>Adjust your filters or add a new expense.</p></div>
  <?php else: ?>
  <table class="data-table" id="expTable">
    <thead><tr>
      <th>Date</th><th>Description</th><th>Event</th>
      <th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Amount</th>
      <th>Reimbursed</th><th>Receipts</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach ($expenses as $row):
          $attStmt = $pdo->prepare("SELECT * FROM finance_attachments WHERE expense_id=?");
          $attStmt->execute([$row['id']]);
          $atts = $attStmt->fetchAll();
      ?>
      <tr>
        <td class="mono"><?= h($row['date']) ?></td>
        <td>
          <div style="font-weight:500;"><?= h($row['description']) ?></div>
          <?php if ($row['notes']): ?><div style="font-size:12px;color:var(--text-muted);"><?= h($row['notes']) ?></div><?php endif; ?>
        </td>
        <td>
          <?php if ($row['event_name']): ?>
            <span class="badge" style="background:<?= h($row['event_color']) ?>22;color:<?= h($row['event_color']) ?>;">
              <span class="event-dot" style="background:<?= h($row['event_color']) ?>;"></span>
              <?= h($row['event_name']) ?>
            </span>
          <?php else: echo '<span class="text-muted">—</span>'; endif; ?>
        </td>
        <td class="mono text-right"><?= number_format($row['quantity'], 2) ?></td>
        <td class="mono text-right"><?= format_money($row['unit_price']) ?></td>
        <td class="amount-out text-right"><?= format_money($row['amount']) ?></td>
        <td>
          <?php if ($row['is_reimbursed']): ?>
            <span class="badge badge-green">✓ <?= $row['reimbursed_by'] ? h($row['reimbursed_by']) : 'Yes' ?></span>
          <?php else: ?>
            <span class="badge badge-gray">No</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (empty($atts)): ?>
            <span class="text-muted" style="font-size:12px;">None</span>
          <?php else: ?>
            <div class="receipt-gallery" style="gap:4px;margin:0;">
              <?php foreach ($atts as $att):
                  $isImg = str_starts_with($att['mime_type'] ?? '', 'image/');
                  $fileUrl = '/uploads/' . urlencode($att['filename']);
              ?>
              <a href="<?= h($fileUrl) ?>" target="_blank" title="<?= h($att['original']) ?>" class="receipt-thumb" style="width:36px;height:36px;font-size:14px;">
                <?php if ($isImg): ?>
                  <img src="<?= h($fileUrl) ?>" alt="<?= h($att['original']) ?>">
                <?php else: ?>
                  📄
                <?php endif; ?>
              </a>
              <?php endforeach; ?>
              <span style="font-size:11px;color:var(--text-muted);align-self:center;"><?= count($atts) ?></span>
            </div>
          <?php endif; ?>
        </td>
        <td>
          <div class="flex gap-8">
            <button class="btn btn-secondary btn-sm btn-icon" title="Edit" onclick="openModal('modalEdit<?= $row['id'] ?>')">✏️</button>
            <form method="POST" id="del<?= $row['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button type="button" class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="confirmDelete('del<?= $row['id'] ?>','this expense')">🗑</button>
            </form>
          </div>
        </td>
      </tr>

      <!-- Edit modal -->
      <div class="modal-backdrop" id="modalEdit<?= $row['id'] ?>">
        <div class="modal">
          <div class="modal-header">
            <span class="modal-title">Edit Expense</span>
            <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalEdit<?= $row['id'] ?>')">✕</button>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <div class="form-grid">
                <div class="form-group full">
                  <label class="form-label">Description *</label>
                  <input class="form-control" name="description" value="<?= h($row['description']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Event</label>
                  <select class="form-control" name="event_id">
                    <option value="">— None —</option>
                    <?php foreach ($eventsAll as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $row['event_id']==$ev['id']?'selected':'' ?>><?= h($ev['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Date</label>
                  <input class="form-control" type="date" name="date" value="<?= h($row['date']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Quantity</label>
                  <input class="form-control" type="number" name="qty" id="qty_<?= $row['id'] ?>" value="<?= $row['quantity'] ?>" step="0.001" min="0.001">
                </div>
                <div class="form-group">
                  <label class="form-label">Unit Price (₱)</label>
                  <input class="form-control" type="number" name="unit_price" id="unit_price_<?= $row['id'] ?>" value="<?= $row['unit_price'] ?>" step="0.01" min="0">
                </div>
                <div class="form-group full">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" rows="2"><?= h($row['notes']) ?></textarea>
                </div>
                <div class="form-group full">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_reimbursed" <?= $row['is_reimbursed']?'checked':'' ?>> Reimbursed
                  </label>
                </div>
                <div class="form-group">
                  <label class="form-label">Reimbursed By</label>
                  <input class="form-control" name="reimbursed_by" value="<?= h($row['reimbursed_by']) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Reimbursement Date</label>
                  <input class="form-control" type="date" name="reimbursed_date" value="<?= h($row['reimbursed_date']) ?>">
                </div>
                <div class="form-group full">
                  <label class="form-label">Add More Receipts</label>
                  <div class="upload-zone" id="zone_<?= $row['id'] ?>">📎 Click or drag files here</div>
                  <input type="file" name="receipts[]" id="file_<?= $row['id'] ?>" multiple style="display:none;">
                  <?php if (!empty($atts)): ?>
                  <div style="margin-top:10px;font-size:12px;color:var(--text-muted);">Existing attachments:</div>
                  <div class="receipt-gallery">
                    <?php foreach ($atts as $att):
                        $isImg = str_starts_with($att['mime_type'] ?? '', 'image/');
                        $fileUrl = '/uploads/' . urlencode($att['filename']);
                    ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                      <a href="<?= h($fileUrl) ?>" target="_blank" class="receipt-thumb">
                        <?php if ($isImg): ?><img src="<?= h($fileUrl) ?>" alt=""><?php else: ?>📄<?php endif; ?>
                      </a>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_attach">
                        <input type="hidden" name="attach_id" value="<?= $att['id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit" style="font-size:10px;padding:2px 6px;">✕</button>
                      </form>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit<?= $row['id'] ?>')">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
      <script>initUploadZone('zone_<?= $row['id'] ?>','file_<?= $row['id'] ?>');</script>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Create modal -->
<div class="modal-backdrop" id="modalCreate">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Expense</span>
      <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalCreate')">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Description *</label>
            <input class="form-control" name="description" placeholder="What was purchased?" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Event</label>
            <select class="form-control" name="event_id">
              <option value="">— None —</option>
              <?php foreach ($eventsAll as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= h($ev['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input class="form-control" type="date" name="date" data-today required>
          </div>
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input class="form-control" type="number" name="qty" id="qty" value="1" step="0.001" min="0.001">
          </div>
          <div class="form-group">
            <label class="form-label">Unit Price (₱)</label>
            <input class="form-control" type="number" name="unit_price" id="unit_price" step="0.01" min="0" placeholder="0.00">
          </div>
          <div class="form-group full">
            <label class="form-label">Total Amount (₱)</label>
            <input class="form-control" id="amount_display" readonly placeholder="Auto-calculated">
          </div>
          <div class="form-group full">
            <label class="form-label">Notes (optional)</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Additional details…"></textarea>
          </div>
          <div class="form-group full">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="is_reimbursed"> Mark as Reimbursed
            </label>
          </div>
          <div class="form-group full">
            <label class="form-label">Receipts / Attachments</label>
            <div class="upload-zone" id="uploadZone">📎 Click or drag files here (images, PDFs, etc.)</div>
            <input type="file" name="receipts[]" id="fileInput" multiple style="display:none;">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreate')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Expense</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initUploadZone('uploadZone','fileInput');
    initExpenseCalc();
});
</script>

<?php layout_foot(); ?>
