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

// Folder name => label. Folders not listed here still appear, labelled from the folder name.
$subjectLabels = [
    'filipino'       => 'Filipino',
    'research'       => 'Research',
    'social_science' => 'Social Science',
];

$metadata = json_decode((string) @file_get_contents(__DIR__ . '/metadata.json'), true);
if (!is_array($metadata)) {
    $metadata = [];
}

function repo_format_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

// "2024", "2024-03" or "2024-03-14" => [sortable Y-m-d, display label]; null if not a valid date.
function repo_parse_date($value): ?array
{
    if (!is_string($value) || !preg_match('/^(\d{4})(?:-(\d{2}))?(?:-(\d{2}))?$/', trim($value), $m)) {
        return null;
    }
    $year  = (int) $m[1];
    $month = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
    $day   = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;
    if ($month > 12 || ($month === 0 && $day > 0) || ($day > 0 && !checkdate($month, $day, $year))) {
        return null;
    }
    $sort = sprintf('%04d-%02d-%02d', $year, $month ?: 1, $day ?: 1);
    if ($day) {
        $label = date('F j, Y', strtotime($sort));
    } elseif ($month) {
        $label = date('F Y', strtotime($sort));
    } else {
        $label = (string) $year;
    }
    return [$sort, $label];
}

function repo_string_list($value): array
{
    if (is_string($value)) {
        $value = preg_split('/\s*[,;]\s*/', $value);
    }
    if (!is_array($value)) {
        return [];
    }
    return array_values(array_filter(array_map(function ($v) {
        return is_string($v) ? trim($v) : '';
    }, $value), 'strlen'));
}

$subjects = [];
$papers   = [];

$folders = [];
foreach (scandir(__DIR__ . '/assets') ?: [] as $name) {
    if ($name[0] !== '.' && is_dir(__DIR__ . '/assets/' . $name)) {
        $folders[] = $name;
    }
}
// Known subjects first, in the order above; extra folders after them, alphabetically.
usort($folders, function ($a, $b) use ($subjectLabels) {
    $pa = array_search($a, array_keys($subjectLabels), true);
    $pb = array_search($b, array_keys($subjectLabels), true);
    $pa = $pa === false ? PHP_INT_MAX : $pa;
    $pb = $pb === false ? PHP_INT_MAX : $pb;
    return $pa <=> $pb ?: strcmp($a, $b);
});

foreach ($folders as $folder) {
    $label = $subjectLabels[$folder] ?? ucwords(str_replace(['_', '-'], ' ', $folder));
    $subjects[$folder] = ['label' => $label, 'count' => 0];

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
    <header class="rr-header">
        <div class="rr-header-inner">
            <img src="../img/logo.png" alt="PSHS Logo" class="rr-logo" width="52" height="52">
            <div class="rr-school">
                <span>Department of Science and Technology</span>
                <strong>PHILIPPINE SCIENCE HIGH SCHOOL</strong>
                <strong>ILOCOS REGION CAMPUS</strong>
            </div>
            <a class="rr-home" href="../index.php">Reserve-a-Lab</a>
        </div>
    </header>

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
