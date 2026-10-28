<?php
/*
 * Research repository index.
 *
 * Lists every PDF stored in assets/<subject>/ (assets/filipino, assets/research,
 * assets/social_science, or any other folder added under assets/). A PDF dropped into a
 * subject folder shows up with no other step. Optional details (title, authors, date
 * published, keywords, abstract) come from metadata.json, keyed by the PDF's path
 * relative to this folder. See README.md.
 *
 * The list is filtered in the browser (repository.js); this file only builds the data.
 */

require_once __DIR__ . '/lib.php';

$metadata = repo_read_metadata();
$isAdmin  = repo_viewer_is_admin();

$subjects = [];
$papers   = [];

$folders = repo_subject_folders();

foreach ($folders as $folder) {
    $subjects[$folder] = ['label' => repo_subject_label($folder), 'count' => 0];

    foreach (scandir(__DIR__ . '/assets/' . $folder) ?: [] as $file) {
        $full = __DIR__ . '/assets/' . $folder . '/' . $file;
        if (strcasecmp(pathinfo($file, PATHINFO_EXTENSION), 'pdf') !== 0 || !is_file($full)) {
            continue;
        }

        $key  = 'assets/' . $folder . '/' . $file;
        $info = is_array($metadata[$key] ?? null) ? $metadata[$key] : [];

        $title = isset($info['title']) && is_string($info['title']) && trim($info['title']) !== ''
            ? trim($info['title'])
            : ucfirst(trim(preg_replace('/[\s_]+/', ' ', pathinfo($file, PATHINFO_FILENAME))));

        $date     = repo_parse_date($info['published'] ?? null);
        $dateEst  = $date === null;   // no valid date in metadata.json: fall back to the file's date
        if ($dateEst) {
            $mtime = date('Y-m-d', (int) filemtime($full));
            $date  = repo_parse_date($mtime);
        }

        $subjects[$folder]['count']++;
        $papers[] = [
            'title'     => $title,
            'authors'   => repo_string_list($info['authors'] ?? []),
            'subject'   => $folder,
            'date'      => $date[0],
            'year'      => (int) substr($date[0], 0, 4),
            'dateLabel' => $date[1],
            'dateGuess' => $dateEst,
            'keywords'  => repo_string_list($info['keywords'] ?? []),
            'abstract'  => isset($info['abstract']) && is_string($info['abstract']) ? trim($info['abstract']) : '',
            'size'      => repo_format_size((int) filesize($full)),
            // Each path segment is encoded, so names with spaces or # still open.
            'url'       => implode('/', array_map('rawurlencode', explode('/', $key))),
            'file'      => $file,
        ];
    }
}

$repoData = [
    'subjects' => array_map(function ($k, $s) {
        return ['key' => $k, 'label' => $s['label'], 'count' => $s['count']];
    }, array_keys($subjects), $subjects),
    'papers'   => $papers,
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Research Repository - PSHS-IRC</title>
    <link rel="icon" type="image/x-icon" href="../img/logo.png">
    <link rel="preload" href="../assets/fonts/raleway/raleway-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="repository.css?v=<?= filemtime(__DIR__ . '/repository.css') ?>">
</head>
<body>
    <?php
    $rrNav = $isAdmin ? [['upload.php', 'Upload paper']] : [];
    $rrNav[] = ['../index.php', 'Reserve-a-Lab'];
    include __DIR__ . '/header.inc.php';
    ?>

    <main class="rr-main">
        <section class="rr-intro">
            <h1>Research Repository</h1>
            <p>Search and download research papers by subject, year and keyword.</p>
        </section>

        <section class="rr-panel" aria-label="Search and filters">
            <form id="rr-form" role="search" onsubmit="return false">
                <label class="rr-sr" for="rr-q">Search research</label>
                <div class="rr-search">
                    <input id="rr-q" type="search" placeholder="Search title, author, keyword or abstract" autocomplete="off" spellcheck="false">
                </div>

                <div class="rr-row">
                    <div class="rr-field rr-field-subject">
                        <span class="rr-label" id="rr-subject-label">Subject</span>
                        <div id="rr-subjects" class="rr-chips" role="group" aria-labelledby="rr-subject-label"></div>
                    </div>
                </div>

                <div class="rr-row rr-row-grid">
                    <div class="rr-field">
                        <label class="rr-label" for="rr-from">Published from</label>
                        <select id="rr-from"></select>
                    </div>
                    <div class="rr-field">
                        <label class="rr-label" for="rr-to">Published to</label>
                        <select id="rr-to"></select>
                    </div>
                    <div class="rr-field">
                        <label class="rr-label" for="rr-sort">Sort by</label>
                        <select id="rr-sort">
                            <option value="new">Newest first</option>
                            <option value="old">Oldest first</option>
                            <option value="title">Title (A to Z)</option>
                        </select>
                    </div>
                </div>

                <div class="rr-row" id="rr-tags-row" hidden>
                    <div class="rr-field">
                        <span class="rr-label" id="rr-tags-label">Keywords</span>
                        <div id="rr-tags" class="rr-chips rr-chips-tags" role="group" aria-labelledby="rr-tags-label"></div>
                    </div>
                </div>
            </form>
        </section>

        <div class="rr-status">
            <p id="rr-count" role="status" aria-live="polite"></p>
            <button type="button" id="rr-clear" class="rr-link" hidden>Clear all filters</button>
        </div>

        <ul id="rr-results" class="rr-results"></ul>
        <div class="rr-more"><button type="button" id="rr-more" class="rr-btn rr-btn-ghost" hidden>Show more</button></div>
        <p id="rr-empty" class="rr-empty" hidden></p>
    </main>

    <footer class="rr-footer">PHILIPPINE SCIENCE HIGH SCHOOL - ILOCOS REGION CAMPUS</footer>

    <script id="rr-data" type="application/json"><?= json_encode($repoData, $jsonFlags) ?></script>
    <script src="repository.js?v=<?= filemtime(__DIR__ . '/repository.js') ?>"></script>
</body>
</html>
