<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$pdo  = db();
$user = current_user();
$msg  = '';
$err  = '';

// ── Handle actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name   = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'Planned';
        $color  = $_POST['color']  ?? '#3B82F6';
        $icon   = trim($_POST['icon'] ?? '');
        if ($name === '') { $err = 'Event name is required.'; }
        else {
            $pdo->prepare("INSERT INTO finance_events (name,status,color,icon) VALUES (?,?,?,?)")
                ->execute([$name, $status, $color, $icon]);
            $msg = "Event "{$name}" created.";
        }
    }

    if ($action === 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'Planned';
        $color  = $_POST['color']  ?? '#3B82F6';
        $icon   = trim($_POST['icon'] ?? '');
        if ($id && $name !== '') {
            $old = $pdo->prepare("SELECT * FROM finance_events WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE finance_events SET name=?,status=?,color=?,icon=?,updated_at=NOW() WHERE id=?")
                ->execute([$name, $status, $color, $icon, $id]);
            log_history('finance_events', $id, 'edit', '', json_encode(compact('name','status','color')), $user['username']);
            $msg = 'Event updated.';
        }
    }

    if ($action === 'archive') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE finance_events SET is_archived=1 WHERE id=?")->execute([$id]);
            $msg = 'Event archived.';
        }
    }

    if ($action === 'unarchive') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE finance_events SET is_archived=0 WHERE id=?")->execute([$id]);
            $msg = 'Event restored.';
        }
    }
}

$showArchived = isset($_GET['archived']);
$events = $pdo->query("SELECT e.*,
    (SELECT COALESCE(SUM(amount),0) FROM finance_income   WHERE event_id=e.id AND is_deleted=0) AS income,
    (SELECT COALESCE(SUM(amount),0) FROM finance_expenses WHERE event_id=e.id AND is_deleted=0) AS expenses
    FROM finance_events e
    WHERE e.is_archived=" . ($showArchived ? '1' : '0') . "
    ORDER BY e.created_at DESC")->fetchAll();

layout_head('Events');
layout_sidebar('events');
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">⚠️ <?= h($err) ?></div><?php endif; ?>

<div class="flex justify-between items-center mb-16" style="margin-bottom:16px;flex-wrap:wrap;gap:8px;">
  <div class="flex gap-8">
    <a href="?<?= $showArchived ? '' : 'archived' ?>" class="btn btn-secondary btn-sm">
      <?= $showArchived ? '← Active Events' : '📦 Show Archived' ?>
    </a>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalCreate')">+ New Event</button>
</div>

<?php if (empty($events)): ?>
  <div class="card"><div class="empty-state">
    <div class="empty-icon">📅</div>
    <h3><?= $showArchived ? 'No archived events' : 'No events yet' ?></h3>
    <p>Create your first event to start tracking finances by project.</p>
  </div></div>
<?php else: ?>
<div class="grid-2" style="gap:16px;">
  <?php foreach ($events as $ev):
      $balance = $ev['income'] - $ev['expenses'];
      $pct     = $ev['income'] > 0 ? min(100, round($ev['expenses'] / $ev['income'] * 100)) : 0;
      $bCls    = $pct >= 90 ? 'red' : ($pct >= 60 ? 'amber' : 'green');
      $statusCls = match($ev['status']) { 'Active' => 'badge-green', 'Completed' => 'badge-blue', default => 'badge-gray' };
  ?>
  <div class="card" style="border-top:3px solid <?= h($ev['color']) ?>;">
    <div class="card-header">
      <div class="flex items-center gap-8">
        <?php if ($ev['icon']): ?><span style="font-size:20px;"><?= h($ev['icon']) ?></span><?php endif; ?>
        <span class="card-title"><?= h($ev['name']) ?></span>
      </div>
      <span class="badge <?= $statusCls ?>"><?= h($ev['status']) ?></span>
    </div>
    <div class="card-body">
      <div class="grid-2" style="gap:12px;margin-bottom:14px;">
        <div style="text-align:center;">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">INCOME</div>
          <div style="font-size:15px;font-weight:700;color:var(--success);font-family:'DM Mono',monospace;"><?= format_money($ev['income']) ?></div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px;">EXPENSES</div>
          <div style="font-size:15px;font-weight:700;color:var(--danger);font-family:'DM Mono',monospace;"><?= format_money($ev['expenses']) ?></div>
        </div>
      </div>
      <div class="progress-bar-wrap" style="margin-bottom:8px;"><div class="progress-bar <?= $bCls ?>" style="width:<?= $pct ?>%"></div></div>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
        Balance: <strong style="color:<?= $balance >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= format_money($balance) ?></strong> &nbsp;·&nbsp; <?= $pct ?>% budget used
      </div>
      <div class="flex gap-8">
        <button class="btn btn-secondary btn-sm"
          onclick="openModal('modalEdit<?= $ev['id'] ?>')">✏️ Edit</button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="action" value="<?= $ev['is_archived'] ? 'unarchive' : 'archive' ?>">
          <input type="hidden" name="id" value="<?= $ev['id'] ?>">
          <button class="btn btn-secondary btn-sm" type="submit"><?= $ev['is_archived'] ? '♻️ Restore' : '📦 Archive' ?></button>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit modal for this event -->
  <div class="modal-backdrop" id="modalEdit<?= $ev['id'] ?>">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Edit Event</span>
        <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalEdit<?= $ev['id'] ?>')">✕</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $ev['id'] ?>">
          <div class="form-grid">
            <div class="form-group full">
              <label class="form-label">Event Name *</label>
              <input class="form-control" name="name" value="<?= h($ev['name']) ?>" required autofocus>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-control" name="status">
                <?php foreach (['Planned','Active','Completed'] as $s): ?>
                <option value="<?= $s ?>" <?= $ev['status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Color</label>
              <input class="form-control" type="color" name="color" value="<?= h($ev['color']) ?>">
            </div>
            <div class="form-group full">
              <label class="form-label">Icon (emoji)</label>
              <input class="form-control" name="icon" value="<?= h($ev['icon']) ?>" placeholder="e.g. 🎄">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit<?= $ev['id'] ?>')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create modal -->
<div class="modal-backdrop" id="modalCreate">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">New Event / Project</span>
      <button class="btn btn-secondary btn-sm btn-icon" onclick="closeModal('modalCreate')">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label">Event Name *</label>
            <input class="form-control" name="name" placeholder="e.g. Teachers Day" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-control" name="status">
              <option value="Planned">Planned</option>
              <option value="Active">Active</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Color</label>
            <input class="form-control" type="color" name="color" value="#3B82F6">
          </div>
          <div class="form-group full">
            <label class="form-label">Icon (emoji, optional)</label>
            <input class="form-control" name="icon" placeholder="e.g. 🎄 🏆 📚">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreate')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Event</button>
      </div>
    </form>
  </div>
</div>

<?php layout_foot(); ?>
