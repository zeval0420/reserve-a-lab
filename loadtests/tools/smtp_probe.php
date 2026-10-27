<?php
// Checks that outbound SMTP is blocked: opens (and immediately closes) a TCP
// connection to the configured SMTP host, sends nothing, and reports how long
// the attempt took. Run from the CLI or through Apache (localhost only).
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}
header('Content-Type: text/plain');
include __DIR__ . '/../../helperFiles/email_credentials.php';
foreach ([$email_smtp_port, 465] as $port) {
    $t = microtime(true);
    $fp = @stream_socket_client("tcp://{$email_smtp_host}:{$port}", $errno, $errstr, 10);
    $ms = round((microtime(true) - $t) * 1000, 1);
    if ($fp) {
        fclose($fp);
        echo "OPEN {$email_smtp_host}:{$port} in {$ms} ms  <-- NOT BLOCKED\n";
    } else {
        echo "BLOCKED {$email_smtp_host}:{$port} in {$ms} ms ({$errno}: {$errstr})\n";
    }
}
