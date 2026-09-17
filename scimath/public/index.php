<?php
require_once __DIR__ . '/../src/bootstrap.php';

$events = Event::allOrderedByDate();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SciMath Competition System</title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="/index.php" class="brand">SciMath Competition System</a>
    </div>
</header>
<main class="page">
    <h1>SciMath Competition System</h1>
    <p class="subtitle">Manage competitions: configure events, run the live control panel, and broadcast results to the venue screen.</p>

    <div class="two-col" style="margin-bottom:8px;">
        <div class="card">
            <h2>Admin Panel</h2>
            <p class="muted">Create and configure events — add categories, contestants, questions, and scoring settings.</p>
            <p><a class="btn btn-primary" href="/admin/login.php">Open Admin Panel</a></p>
        </div>
        <div class="card">
            <h2>Operator Console</h2>
            <p class="muted">Run a live competition — control the timer, reveal questions, and score contestants in real time.</p>
            <p><a class="btn btn-primary" href="/operator/index.php">Open Operator Console</a></p>
        </div>
    </div>

    <div class="card">
        <h2 style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <span>Events</span>
            <a class="btn btn-small" href="/admin/index.php">Manage events</a>
        </h2>

        <?php if ($events === []): ?>
            <p class="muted">No events yet. <a href="/admin/index.php">Create your first event</a> to get started.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Name</th><th>Date</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($ev['name']) ?>
                        <?php if ($ev['subtitle']): ?><br><span class="muted"><?= htmlspecialchars($ev['subtitle']) ?></span><?php endif; ?>
                    </td>
                    <td><?= $ev['event_date'] ? htmlspecialchars($ev['event_date']) : '<span class="muted">—</span>' ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($ev['status']) ?>"><?= htmlspecialchars($ev['status']) ?></span></td>
                    <td class="row-actions">
                        <a class="btn btn-small" href="/display/event.php?id=<?= (int) $ev['id'] ?>">Display</a>
                        <a class="btn btn-small" href="/operator/event.php?id=<?= (int) $ev['id'] ?>">Operate</a>
                        <a class="btn btn-small" href="/admin/event.php?id=<?= (int) $ev['id'] ?>">Configure</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <p class="muted" style="font-size:0.85rem;">Open the <strong>Display</strong> link for an event on the venue projector — no login required.</p>
</main>
</body>
</html>