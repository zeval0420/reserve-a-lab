<?php
/*
 * Add a paper to the research repository. Admin only.
 *
 * GET  shows the form.
 * POST (sent by upload.js) validates the PDF and its details, stores the PDF in
 *      assets/<subject>/, records the details in metadata.json, and answers with JSON.
 */

require_once __DIR__ . '/../helperFiles/session_handler.php';
require_once __DIR__ . '/../helperFiles/upload_security.php';
require_once __DIR__ . '/lib.php';

$folders = repo_subject_folders();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ral_require_role(['admin']);

    // A body over post_max_size arrives empty, which would otherwise look like a bad CSRF token.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        ral_deny(413, 'The PDF is too large (limit ' . (REPO_MAX_PDF_BYTES / 1048576) . ' MB).');
    }
    ral_require_csrf();

    $text = function ($name, $max) {
        $v = $_POST[$name] ?? '';
        return is_string($v) ? mb_substr(trim(preg_replace('/\s+/u', ' ', $v)), 0, $max) : '';
    };

    $subject = is_string($_POST['subject'] ?? null) ? $_POST['subject'] : '';
    if (!in_array($subject, $folders, true)) {   // never a path from the client
        ral_deny(422, 'Choose a subject.');
    }

    $title = $text('title', 250);
    if ($title === '') {
        ral_deny(422, 'Enter the title.');
    }

    $published = is_string($_POST['published'] ?? null) ? trim($_POST['published']) : '';
    $date = repo_parse_date($published);
    if ($date === null) {
        ral_deny(422, 'Enter the date published as YYYY, YYYY-MM or YYYY-MM-DD.');
    }
    if ($date[0] > date('Y-m-d')) {
        ral_deny(422, 'The date published cannot be in the future.');
    }

    $authors  = array_slice(array_map(function ($v) { return mb_substr($v, 0, 120); }, repo_string_list($text('authors', 2000))), 0, 30);
    $keywords = [];
    foreach (repo_string_list($text('keywords', 1500)) as $k) {
        $k = mb_substr($k, 0, 60);
        $keywords[mb_strtolower($k)] = $keywords[mb_strtolower($k)] ?? $k;   // no duplicates, first spelling wins
    }
    $keywords = array_slice(array_values($keywords), 0, 20);
    $abstract = mb_substr(trim((string) ($_POST['abstract'] ?? '')), 0, 4000);

    $check = repo_validate_pdf($_FILES['pdf'] ?? []);
    if (!$check['ok']) {
        ral_deny(422, $check['error']);
    }

    // File name: the uploaded name, else the title; made safe, never overwriting an existing paper.
    $dir  = __DIR__ . '/assets/' . $subject . '/';
    $base = ral_safe_file_base($_FILES['pdf']['name'] ?? '') ?: ral_safe_file_base($title) ?: 'paper';
    $path = '';
    for ($n = 1; $n < 1000; $n++) {
        $candidate = $dir . $base . ($n > 1 ? '_' . $n : '') . '.pdf';
        $h = @fopen($candidate, 'xb');   // creates the file only if it is free, so two uploads cannot pick the same name
        if ($h) {
            fclose($h);
            $path = $candidate;
            break;
        }
    }
    if ($path === '' || !move_uploaded_file($_FILES['pdf']['tmp_name'], $path)) {
        if ($path !== '') {
            @unlink($path);
        }
        ral_deny(500, 'The PDF could not be saved. Please try again.');
    }

    $file  = basename($path);
    $entry = ['title' => $title, 'published' => $published];
    if ($authors)  { $entry['authors']  = $authors; }
    if ($keywords) { $entry['keywords'] = $keywords; }
    if ($abstract !== '') { $entry['abstract'] = $abstract; }

    if (!repo_save_metadata('assets/' . $subject . '/' . $file, $entry)) {
        @unlink($path);   // no half-added paper: a PDF with no details would show the wrong title and date
        ral_deny(500, 'The details could not be saved. Nothing was added.');
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'success',
        'success' => true,
        'message' => 'Added "' . $title . '" to ' . repo_subject_label($subject) . '.',
        'url'     => 'assets/' . rawurlencode($subject) . '/' . rawurlencode($file),
    ]);
    exit();
}

// Page: admins only.
if (empty($_SESSION['role'])) {
    header('Location: ../index.php');
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Research - PSHS-IRC</title>
    <link rel="icon" type="image/x-icon" href="../img/logo.png">
    <link rel="preload" href="../assets/fonts/raleway/raleway-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="repository.css?v=<?= filemtime(__DIR__ . '/repository.css') ?>">
</head>
<body>
    <?php
    $rrNav = [['index.php', 'Repository'], ['../index.php', 'Reserve-a-Lab']];
    include __DIR__ . '/header.inc.php';
    ?>

    <main class="rr-main rr-main-narrow">
        <section class="rr-intro">
            <h1>Add a paper</h1>
            <p>Upload a PDF and describe it. It appears in the repository straight away.</p>
        </section>

        <div id="rr-result" class="rr-notice" role="status" aria-live="polite" hidden></div>

        <form id="rr-upload" class="rr-panel rr-form" action="upload.php" method="post" enctype="multipart/form-data" novalidate
              data-max-bytes="<?= REPO_MAX_PDF_BYTES ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="rr-field">
                <label class="rr-label" for="rr-pdf">PDF file <span class="rr-req">required</span></label>
                <div class="rr-drop" id="rr-drop">
                    <input id="rr-pdf" name="pdf" type="file" accept="application/pdf,.pdf" required>
                    <p id="rr-drop-text">Drop a PDF here or choose one. Up to <?= REPO_MAX_PDF_BYTES / 1048576 ?> MB.</p>
                </div>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-subject">Subject <span class="rr-req">required</span></label>
                <select id="rr-subject" name="subject" required>
                    <option value="">Choose a subject</option>
                    <?php foreach ($folders as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars(repo_subject_label($f)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-title">Title <span class="rr-req">required</span></label>
                <input class="rr-input" id="rr-title" name="title" type="text" maxlength="250" required>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-published">Date published <span class="rr-req">required</span></label>
                <input class="rr-input" id="rr-published" name="published" type="text" inputmode="numeric" maxlength="10"
                       placeholder="2025, 2025-03 or 2025-03-14" pattern="\d{4}(-\d{2}(-\d{2})?)?" required aria-describedby="rr-published-hint">
                <p class="rr-hint" id="rr-published-hint">Year only is fine if the exact date is not known.</p>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-authors">Authors</label>
                <input class="rr-input" id="rr-authors" name="authors" type="text" maxlength="2000" aria-describedby="rr-authors-hint">
                <p class="rr-hint" id="rr-authors-hint">Separate names with commas.</p>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-keywords">Keywords</label>
                <input class="rr-input" id="rr-keywords" name="keywords" type="text" maxlength="1500" aria-describedby="rr-keywords-hint">
                <p class="rr-hint" id="rr-keywords-hint">Separate with commas. People can filter by these.</p>
            </div>

            <div class="rr-field">
                <label class="rr-label" for="rr-abstract">Abstract</label>
                <textarea class="rr-input" id="rr-abstract" name="abstract" rows="5" maxlength="4000"></textarea>
            </div>

            <p id="rr-error" class="rr-error" role="alert" hidden></p>

            <div class="rr-form-actions">
                <button type="submit" class="rr-btn" id="rr-submit">Add paper</button>
                <a class="rr-btn rr-btn-ghost" href="index.php">Back to repository</a>
            </div>
        </form>
    </main>

    <footer class="rr-footer">PHILIPPINE SCIENCE HIGH SCHOOL - ILOCOS REGION CAMPUS</footer>

    <script src="upload.js?v=<?= filemtime(__DIR__ . '/upload.js') ?>"></script>
</body>
</html>
