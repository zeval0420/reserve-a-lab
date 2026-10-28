<?php
/*
 * Shared helpers for the research repository (index.php and upload.php).
 */

const REPO_MAX_PDF_BYTES = 25 * 1024 * 1024;   // per PDF; PHP's own limit (upload_max_filesize) is higher

// Folder name => label. Folders not listed here still appear, labelled from the folder name.
const REPO_SUBJECT_LABELS = [
    'filipino'       => 'Filipino',
    'research'       => 'Research',
    'social_science' => 'Social Science',
];

/** Subject folders under assets/: the known ones first, in the order above, then the rest alphabetically. */
function repo_subject_folders(): array
{
    $folders = [];
    foreach (scandir(__DIR__ . '/assets') ?: [] as $name) {
        if ($name[0] !== '.' && is_dir(__DIR__ . '/assets/' . $name)) {
            $folders[] = $name;
        }
    }
    $known = array_keys(REPO_SUBJECT_LABELS);
    usort($folders, function ($a, $b) use ($known) {
        $pa = array_search($a, $known, true);
        $pb = array_search($b, $known, true);
        $pa = $pa === false ? PHP_INT_MAX : $pa;
        $pb = $pb === false ? PHP_INT_MAX : $pb;
        return $pa <=> $pb ?: strcmp($a, $b);
    });
    return $folders;
}

function repo_subject_label(string $folder): string
{
    return REPO_SUBJECT_LABELS[$folder] ?? ucwords(str_replace(['_', '-'], ' ', $folder));
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
    if ($year < 1
        || (isset($m[2]) && $m[2] !== '' && ($month < 1 || $month > 12))
        || (isset($m[3]) && $m[3] !== '' && !checkdate($month, $day, $year))) {
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

/** A list of strings, or one string separated by commas or semicolons, as a clean list. */
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

/** metadata.json as an array ([] when missing or unreadable). Waits for a writer to finish. */
function repo_read_metadata(): array
{
    $h = @fopen(__DIR__ . '/metadata.json', 'rb');
    if (!$h) {
        return [];
    }
    flock($h, LOCK_SH);
    $raw = stream_get_contents($h);
    flock($h, LOCK_UN);
    fclose($h);
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Add or replace one entry in metadata.json. The file is locked for the whole
 * read-modify-write, so two uploads at once cannot lose each other's entry. A file that
 * is not valid JSON is left untouched and the call fails.
 */
function repo_save_metadata(string $key, array $entry): bool
{
    $h = @fopen(__DIR__ . '/metadata.json', 'c+b');
    if (!$h) {
        return false;
    }
    if (!flock($h, LOCK_EX)) {
        fclose($h);
        return false;
    }
    $ok  = false;
    $raw = (string) stream_get_contents($h);
    $all = trim($raw) === '' ? [] : json_decode($raw, true);
    if (is_array($all)) {
        $all[$key] = $entry;
        $json = json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $ok = ftruncate($h, 0) && rewind($h) && fwrite($h, $json . "\n") !== false && fflush($h);
        }
    }
    flock($h, LOCK_UN);
    fclose($h);
    return $ok;
}

/**
 * Check one uploaded PDF ($_FILES entry shape). The client's file name and declared type
 * decide nothing: the bytes must be a PDF.
 *
 * @return array ['ok' => true] or ['ok' => false, 'error' => 'message']
 */
function repo_validate_pdf(array $file): array
{
    $fail = function ($msg) {
        return ['ok' => false, 'error' => $msg];
    };

    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return $fail('The PDF is too large (limit ' . (REPO_MAX_PDF_BYTES / 1048576) . ' MB).');
    }
    if ($err === UPLOAD_ERR_NO_FILE) {
        return $fail('Choose a PDF file.');
    }
    if ($err !== UPLOAD_ERR_OK) {
        return $fail('The upload failed. Please try again.');
    }
    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        return $fail('The upload failed. Please try again.');
    }
    $size = filesize($tmp);
    if ($size === false || $size <= 0) {
        return $fail('The PDF is empty.');
    }
    if ($size > REPO_MAX_PDF_BYTES) {
        return $fail('The PDF is too large (limit ' . (REPO_MAX_PDF_BYTES / 1048576) . ' MB).');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $head = (string) file_get_contents($tmp, false, null, 0, 1024);
    if ($mime !== 'application/pdf' || strpos($head, '%PDF-') === false) {
        return $fail('Only PDF files are allowed.');
    }

    // A script hidden inside an otherwise valid file (polyglot).
    $body = file_get_contents($tmp);
    if ($body === false || stripos($body, '<?php') !== false) {
        return $fail('The file is not a valid PDF.');
    }
    return ['ok' => true];
}

/**
 * True when the visitor has an admin session. Reads the session without locking or
 * extending it, and starts none for a visitor who has no session cookie, so the public
 * index stays public.
 */
function repo_viewer_is_admin(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        if (empty($_COOKIE[session_name()])) {
            return false;
        }
        session_start(['read_and_close' => true]);
    }
    return ($_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['email']);
}
