<?php
/**
 * api/email_test.php
 * GET              -> { success, mail: {problem, smtp, recipients, send_after_response, smtp_login}, recent: [...] }
 * GET ?send=latest -> also emails the newest accepted session right now and reports the result
 *
 * Admin-only (log in on the Settings page first, then open this in the same
 * browser). For finding out why accepted strips aren't being emailed.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
admin_require_api();
@set_time_limit(300);

$recent = [];
foreach (SessionManager::listAll() as $s) {
    if (empty($s['accepted_at'])) {
        continue;
    }
    $recent[] = array_intersect_key($s, array_flip(['session_id', 'accepted_at', 'email_status', 'email_sent_at', 'email_error']));
    if (count($recent) === 5) {
        break;
    }
}

$out = ['success' => true, 'mail' => Mailer::diagnose(), 'recent' => $recent];

if (($_GET['send'] ?? '') === 'latest') {
    if (!$recent) {
        $out['send'] = 'No accepted session to send.';
    } else {
        $id = $recent[0]['session_id'];
        Mailer::sendAndRecord($id);
        $meta = SessionManager::getMetadata($id);
        $out['send'] = ['session_id' => $id, 'email_status' => $meta['email_status'] ?? null, 'email_error' => $meta['email_error'] ?? null];
    }
}

json_response($out);
