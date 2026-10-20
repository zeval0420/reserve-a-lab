<?php
/**
 * tools/optimize_images.php: builds the WebP mirror of img/ (plan.md, contract C2).
 *
 * For every jpg/jpeg/png under img/ (except img/_opt/ itself) it writes
 *   img/_opt/<rel>.webp        (only when smaller than the original; otherwise an empty <rel>.webp.none marker)
 *   img/_opt/<rel>.640w.webp   (only when the image is wider than 640 px)
 * A mirror is rebuilt only when it is missing or older than its original, so running the script
 * again does nothing. Mirrors whose original no longer exists are removed, then empty folders.
 * Originals are never modified.
 *
 * Usage (command line only):
 *   php tools/optimize_images.php            build missing or outdated mirrors, remove orphans
 *   php tools/optimize_images.php --force    rebuild every mirror
 *   php tools/optimize_images.php --verbose  list every file
 */

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/plain');
    }
    echo "This script runs from the command line only.\n";
    exit(1);
}

require_once dirname(__DIR__) . '/helperFiles/image_optimizer.php';

$force   = in_array('--force', $argv, true);
$verbose = in_array('--verbose', $argv, true) || in_array('-v', $argv, true);

$root = ral_img_root();
if ($root === null) {
    fwrite(STDERR, "img/ folder not found\n");
    exit(1);
}
$opt = $root . '/_opt';

/** Every file under $dir (recursive), as forward-slash absolute paths, sorted. */
function list_files($dir, $skipDir = null)
{
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            function ($file) use ($skipDir) {
                $path = str_replace('\\', '/', $file->getPathname());
                return $skipDir === null || strcasecmp($path, $skipDir) !== 0;
            }
        )
    );
    foreach ($it as $file) {
        if ($file->isFile()) {
            $out[] = str_replace('\\', '/', $file->getPathname());
        }
    }
    sort($out, SORT_STRING);
    return $out;
}

$counts = ['current' => 0, 'built' => 0, 'skipped' => 0, 'removed' => 0];
$start = microtime(true);

// 1. Build or refresh mirrors.
foreach (list_files($root, $opt) as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        continue;
    }
    $rel = substr($file, strlen($root) + 1);
    $mirror = $opt . '/' . $rel . '.webp';
    $marker = $opt . '/' . $rel . '.webp.none';
    $before = [is_file($mirror) ? filemtime($mirror) : null, is_file($marker) ? filemtime($marker) : null];
    $t = microtime(true);
    $ok = ral_img_write_mirror($file, $force);
    clearstatcache();
    $after = [is_file($mirror) ? filemtime($mirror) : null, is_file($marker) ? filemtime($marker) : null];
    $thumb = is_file($opt . '/' . $rel . '.640w.webp');
    if (!$ok) {
        $counts['skipped']++;
        $state = 'SKIPPED (not converted, see the PHP error log if unexpected)';
    } elseif (!$force && $before === $after && ($before[0] !== null || $before[1] !== null)) {
        $counts['current']++;
        $state = 'up to date';
    } else {
        $counts['built']++;
        $state = sprintf('built in %.1fs: %s%s', microtime(true) - $t,
            is_file($mirror) ? 'webp ' . filesize($mirror) . ' B' : 'no full-size webp (not smaller)',
            $thumb ? ', 640w ' . filesize($opt . '/' . $rel . '.640w.webp') . ' B' : '');
    }
    if ($verbose || $state !== 'up to date') {
        echo $rel . ': ' . $state . "\n";
    }
}

// 2. Remove mirrors whose original is gone.
foreach (list_files($opt) as $file) {
    $rel = substr($file, strlen($opt) + 1);
    if (preg_match('/^(.+\.(?:jpe?g|png))\.(?:640w\.webp|webp|webp\.none)$/i', $rel, $m)) {
        $original = $root . '/' . $m[1];
        if (is_file($original)) {
            continue;
        }
    } elseif (preg_match('/\.tmp[0-9a-f]{8}$/', $rel) && filemtime($file) > time() - 3600) {
        continue;                                       // a write in progress (upload running right now)
    }
    if (unlink($file)) {
        $counts['removed']++;
        echo $rel . ": removed (orphan)\n";
    }
}

// 3. Remove empty folders under img/_opt/ (deepest first).
if (is_dir($opt)) {
    $dirs = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($opt, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        if ($f->isDir()) {
            $dirs[] = $f->getPathname();
        }
    }
    foreach ($dirs as $d) {
        if (count(scandir($d)) === 2) {
            @rmdir($d);
        }
    }
}

printf("Done in %.1fs: %d built, %d up to date, %d skipped, %d orphans removed.\n",
    microtime(true) - $start, $counts['built'], $counts['current'], $counts['skipped'], $counts['removed']);
