<?php
/**
 * image_optimizer.php
 * GD helpers that keep lab images small and keep the WebP mirror in sync.
 *
 * WebP mirror (see plan.md, contract C2): for an original img/<rel> (jpg, jpeg or png)
 *   img/_opt/<rel>.webp        same size as the original, WebP (written only when smaller than the original)
 *   img/_opt/<rel>.640w.webp   640 px wide WebP, only when the original is wider than 640 px
 *   img/_opt/<rel>.webp.none   empty marker: "the full-size WebP would not be smaller", so it is not rebuilt every run
 * .htaccess serves img/_opt/<rel>.webp in place of img/<rel> when the browser accepts WebP.
 *
 * Every public function catches its own errors (Throwable), logs them with error_log() and returns
 * false. None of them prints anything, so they can never break an upload or change an AJAX response.
 *
 * Public functions:
 *   ral_img_process_upload($path)              downsize in place, then rebuild the mirror (for upload code)
 *   ral_img_downsize($path)                    downsize/recompress in place (longest edge <= 1920 px, JPEG q82 progressive)
 *   ral_img_write_mirror($path, $force=false)  write or refresh the mirror and the 640 px thumbnail
 *   ral_img_delete_mirror($path)               delete the mirror files of an original
 *   ral_img_rename_mirror_dir($old, $new)      rename the mirror folder after a gallery folder was renamed
 *   ral_img_mirror_info($relPath)              URLs (with ?v=filemtime) and size of an original's mirror, for pages
 *
 * No closing PHP tag on purpose: this file must never send output.
 */

if (!defined('RAL_IMG_MAX_EDGE')) {
    define('RAL_IMG_MAX_EDGE', 1920);     // longest edge after downsizing
    define('RAL_IMG_JPEG_QUALITY', 82);   // JPEG quality for recompressed originals
    define('RAL_IMG_WEBP_QUALITY', 80);   // lossy WebP quality for mirrors of photos
    define('RAL_IMG_THUMB_WIDTH', 640);   // width of the .640w.webp thumbnail
}

/* ─── PATHS ───────────────────────────────────────────────────── */

/** Absolute path of the project's img/ folder (forward slashes, no trailing slash). */
function ral_img_root()
{
    $root = realpath(dirname(__DIR__) . '/img');
    return $root === false ? null : rtrim(str_replace('\\', '/', $root), '/');
}

/** Normalises a path: forward slashes, resolved through realpath() when it exists. */
function _ral_img_norm($path)
{
    $real = realpath($path);
    if ($real === false) {
        // The file itself may be gone (deleted image): resolve its folder instead.
        $dir = realpath(dirname($path));
        if ($dir === false) {
            return null;
        }
        $real = $dir . '/' . basename($path);
    }
    return str_replace('\\', '/', $real);
}

/**
 * Path of $path relative to img/ ("labimages/Lab 1.jpg"), or null when it is not inside img/
 * or is inside img/_opt/.
 */
function _ral_img_rel($path)
{
    $root = ral_img_root();
    $abs  = _ral_img_norm($path);
    if ($root === null || $abs === null) {
        return null;
    }
    $prefix = $root . '/';
    $windows = (DIRECTORY_SEPARATOR === '\\');
    $head = substr($abs, 0, strlen($prefix));
    if ($windows ? strcasecmp($head, $prefix) !== 0 : $head !== $prefix) {
        return null;
    }
    $rel = substr($abs, strlen($prefix));
    if ($rel === '' || $rel === false) {
        return null;
    }
    $first = explode('/', $rel)[0];
    if ($windows ? strcasecmp($first, '_opt') === 0 : $first === '_opt') {
        return null;
    }
    return $rel;
}

/** True when the extension is one the mirror handles (jpg, jpeg, png). */
function _ral_img_is_mirrorable($path)
{
    return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
}

/** Mirror file paths for an original: ['full' => ..., 'thumb' => ..., 'none' => ...]. */
function _ral_img_mirror_paths($rel)
{
    $base = ral_img_root() . '/_opt/' . $rel;
    return [
        'full'  => $base . '.webp',
        'thumb' => $base . '.640w.webp',
        'none'  => $base . '.webp.none',
    ];
}

/* ─── ERROR HANDLING ──────────────────────────────────────────── */

/**
 * Runs $fn with PHP warnings turned into exceptions (so a GD warning can never be printed
 * into an AJAX response) and with every Throwable caught and logged. Returns $fn's result,
 * or false on any error.
 */
function _ral_img_guard($label, callable $fn)
{
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;                               // silenced with @ or by error_reporting
        }
        if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE], true)) {
            return true;                                // harmless: swallow, never print
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    try {
        return $fn();
    } catch (Throwable $e) {
        error_log('[image_optimizer] ' . $label . ': ' . $e->getMessage());
        return false;
    } finally {
        restore_error_handler();
    }
}

/** Keeps a long run of image work from hitting max_execution_time (never shortens the limit). */
function _ral_img_extend_time_limit()
{
    $limit = (int) ini_get('max_execution_time');
    if ($limit > 0 && function_exists('set_time_limit')) {
        @set_time_limit(max($limit, 60));
    }
}

/** True when GD has what we need. */
function _ral_img_gd_ok()
{
    return function_exists('imagecreatetruecolor') && function_exists('imagecreatefromjpeg')
        && function_exists('imagecreatefrompng') && function_exists('imagejpeg');
}

/** memory_limit in bytes (-1 = unlimited). */
function _ral_img_memory_limit()
{
    $v = trim((string) ini_get('memory_limit'));
    if ($v === '' || $v === '-1') {
        return -1;
    }
    $n = (int) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return $n;
}

/** True when decoding a $w x $h image (plus a working copy) fits in the memory left. */
function _ral_img_memory_ok($w, $h)
{
    $limit = _ral_img_memory_limit();
    if ($limit < 0) {
        return true;
    }
    $target = RAL_IMG_MAX_EDGE * RAL_IMG_MAX_EDGE;          // resized copy, worst case
    $need = (int) (($w * $h + $target) * 5.2) + 24 * 1024 * 1024;
    return memory_get_usage(true) + $need < $limit;
}

/* ─── FILE FORMAT DETAILS (EXIF orientation, ICC profile, PNG alpha) ── */

/**
 * Reads a JPEG's markers: EXIF orientation (1..8, 1 when absent), the raw APP2 ICC segments
 * (complete markers, to copy into a re-encoded JPEG) and the assembled ICC profile bytes.
 */
function _ral_img_jpeg_meta($path)
{
    $meta = ['orientation' => 1, 'icc_segments' => '', 'icc' => ''];
    $fh = fopen($path, 'rb');
    if (!$fh) {
        return $meta;
    }
    $iccParts = [];
    try {
        if (fread($fh, 2) !== "\xFF\xD8") {
            return $meta;
        }
        while (!feof($fh)) {
            $b = fread($fh, 1);
            if ($b !== "\xFF") {
                break;
            }
            $marker = ord(fread($fh, 1));
            while ($marker === 0xFF) {                 // fill bytes
                $marker = ord(fread($fh, 1));
            }
            if ($marker === 0xD8 || ($marker >= 0xD0 && $marker <= 0xD7) || $marker === 0x01) {
                continue;                               // markers without a length
            }
            if ($marker === 0xDA || $marker === 0xD9) {
                break;                                  // start of scan / end: no more metadata
            }
            $lenBytes = fread($fh, 2);
            if (strlen($lenBytes) < 2) {
                break;
            }
            $len = unpack('n', $lenBytes)[1];
            if ($len < 2) {
                break;
            }
            $data = $len > 2 ? fread($fh, $len - 2) : '';
            if (strlen($data) !== $len - 2) {
                break;
            }
            if ($marker === 0xE1 && substr($data, 0, 6) === "Exif\0\0") {
                $o = _ral_img_exif_orientation(substr($data, 6));
                if ($o !== null) {
                    $meta['orientation'] = $o;
                }
            } elseif ($marker === 0xE2 && substr($data, 0, 12) === "ICC_PROFILE\0" && strlen($data) > 14) {
                $meta['icc_segments'] .= "\xFF\xE2" . $lenBytes . $data;
                $iccParts[ord($data[12])] = substr($data, 14);
            }
        }
    } finally {
        fclose($fh);
    }
    if ($iccParts) {
        ksort($iccParts);
        $meta['icc'] = implode('', $iccParts);
    }
    return $meta;
}

/** Orientation tag (0x0112) from a TIFF/EXIF block, or null when absent or unreadable. */
function _ral_img_exif_orientation($tiff)
{
    if (strlen($tiff) < 8) {
        return null;
    }
    $order = substr($tiff, 0, 2);
    if ($order === 'II') {
        $u16 = 'v'; $u32 = 'V';
    } elseif ($order === 'MM') {
        $u16 = 'n'; $u32 = 'N';
    } else {
        return null;
    }
    $ifd = unpack($u32, substr($tiff, 4, 4))[1];
    if ($ifd + 2 > strlen($tiff)) {
        return null;
    }
    $count = unpack($u16, substr($tiff, $ifd, 2))[1];
    for ($i = 0; $i < $count; $i++) {
        $entry = $ifd + 2 + $i * 12;
        if ($entry + 12 > strlen($tiff)) {
            return null;
        }
        if (unpack($u16, substr($tiff, $entry, 2))[1] === 0x0112) {
            $value = unpack($u16, substr($tiff, $entry + 8, 2))[1];
            return ($value >= 1 && $value <= 8) ? $value : null;
        }
    }
    return null;
}

/**
 * Reads a PNG's chunks: colour type, whether it has transparency, whether it is animated,
 * EXIF orientation (eXIf chunk), the raw colour chunks (iCCP, sRGB, gAMA, cHRM) to copy into a
 * re-encoded PNG, and the decompressed ICC profile.
 */
function _ral_img_png_meta($path)
{
    $meta = ['color_type' => null, 'alpha' => false, 'animated' => false, 'orientation' => 1,
             'color_chunks' => '', 'icc' => ''];
    $fh = fopen($path, 'rb');
    if (!$fh) {
        return $meta;
    }
    try {
        if (fread($fh, 8) !== "\x89PNG\r\n\x1a\n") {
            return $meta;
        }
        while (!feof($fh)) {
            $head = fread($fh, 8);
            if (strlen($head) < 8) {
                break;
            }
            $len  = unpack('N', substr($head, 0, 4))[1];
            $type = substr($head, 4, 4);
            if ($type === 'IDAT' || $type === 'IEND') {
                break;                                  // metadata we need comes before the image data
            }
            if ($len > 16 * 1024 * 1024) {
                break;
            }
            $data = $len > 0 ? fread($fh, $len) : '';
            $crc  = fread($fh, 4);
            if (strlen($data) !== $len || strlen($crc) !== 4) {
                break;
            }
            switch ($type) {
                case 'IHDR':
                    $meta['color_type'] = ord($data[9]);
                    if ($meta['color_type'] === 4 || $meta['color_type'] === 6) {
                        $meta['alpha'] = true;
                    }
                    break;
                case 'tRNS':
                    $meta['alpha'] = true;
                    break;
                case 'acTL':
                    $meta['animated'] = true;
                    break;
                case 'eXIf':
                    $o = _ral_img_exif_orientation($data);
                    if ($o !== null) {
                        $meta['orientation'] = $o;
                    }
                    break;
                case 'iCCP':
                    $meta['color_chunks'] .= $head . $data . $crc;
                    $nul = strpos($data, "\0");
                    if ($nul !== false && isset($data[$nul + 1]) && ord($data[$nul + 1]) === 0) {
                        $icc = @gzuncompress(substr($data, $nul + 2));
                        if ($icc !== false) {
                            $meta['icc'] = $icc;
                        }
                    }
                    break;
                case 'sRGB':
                case 'gAMA':
                case 'cHRM':
                    $meta['color_chunks'] .= $head . $data . $crc;
                    break;
            }
        }
    } finally {
        fclose($fh);
    }
    return $meta;
}

/**
 * Basic facts about an original, or null when GD cannot (or must not) handle it:
 * type, size as stored and as displayed (after EXIF orientation), metadata.
 */
function _ral_img_inspect($path)
{
    $info = @getimagesize($path);
    if (!$info || empty($info[0]) || empty($info[1])) {
        return null;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $type = $info[2];
    if ($type === IMAGETYPE_JPEG && in_array($ext, ['jpg', 'jpeg'], true)) {
        if (isset($info['channels']) && $info['channels'] == 4) {
            return null;                                // CMYK: GD would get the colours wrong
        }
        $meta = _ral_img_jpeg_meta($path);
        $meta['alpha'] = false;
        $meta['animated'] = false;
    } elseif ($type === IMAGETYPE_PNG && $ext === 'png') {
        $meta = _ral_img_png_meta($path);
        if ($meta['animated']) {
            return null;                                // APNG: GD keeps only the first frame
        }
    } else {
        return null;                                    // other formats, or content not matching the extension
    }
    $w = (int) $info[0];
    $h = (int) $info[1];
    $swap = in_array($meta['orientation'], [5, 6, 7, 8], true);
    return [
        'type' => $type, 'w' => $w, 'h' => $h,
        'dw' => $swap ? $h : $w, 'dh' => $swap ? $w : $h,   // displayed size
    ] + $meta;
}

/* ─── GD OPERATIONS ───────────────────────────────────────────── */

/** Loads an original into a truecolor GD image with alpha kept. */
function _ral_img_load($path, array $img)
{
    $im = ($img['type'] === IMAGETYPE_JPEG) ? imagecreatefromjpeg($path) : imagecreatefrompng($path);
    if (!$im) {
        throw new RuntimeException('GD could not decode ' . $path);
    }
    if (!imageistruecolor($im)) {
        imagepalettetotruecolor($im);
    }
    imagealphablending($im, false);
    imagesavealpha($im, true);
    return $im;
}

/** Resamples to $w x $h, keeping transparency. Frees the source when $free is true. */
function _ral_img_resize($src, $w, $h, $free = true)
{
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
    if ($free) {
        imagedestroy($src);
    }
    return $dst;
}

/** Applies an EXIF orientation (2..8) so the pixels are stored the way they are displayed. */
function _ral_img_orient($im, $orientation)
{
    $rotate = [3 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90];   // GD rotates counter-clockwise
    if (isset($rotate[$orientation])) {
        $rotated = imagerotate($im, $rotate[$orientation], 0);
        if (!$rotated) {
            throw new RuntimeException('imagerotate failed');
        }
        imagedestroy($im);
        $im = $rotated;
        imagealphablending($im, false);
        imagesavealpha($im, true);
    }
    if ($orientation === 2 || $orientation === 5 || $orientation === 7) {
        imageflip($im, IMG_FLIP_HORIZONTAL);
    } elseif ($orientation === 4) {
        imageflip($im, IMG_FLIP_VERTICAL);
    }
    return $im;
}

/** Size that fits $w x $h inside a $max x $max box (never enlarges). */
function _ral_img_fit($w, $h, $max)
{
    if (max($w, $h) <= $max) {
        return [$w, $h];
    }
    $scale = $max / max($w, $h);
    return [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];
}

/** Inserts the original's APP2 ICC segments into GD's JPEG output (after SOI and APP0). */
function _ral_img_jpeg_add_icc($jpeg, $segments)
{
    if ($segments === '' || substr($jpeg, 0, 2) !== "\xFF\xD8") {
        return $jpeg;
    }
    $pos = 2;
    if (substr($jpeg, 2, 2) === "\xFF\xE0") {           // keep the JFIF APP0 first
        $pos = 4 + unpack('n', substr($jpeg, 4, 2))[1];
    }
    return substr($jpeg, 0, $pos) . $segments . substr($jpeg, $pos);
}

/** Inserts raw PNG colour chunks right after IHDR. */
function _ral_img_png_add_chunks($png, $chunks)
{
    if ($chunks === '' || substr($png, 12, 4) !== 'IHDR') {
        return $png;
    }
    $pos = 8 + 8 + unpack('N', substr($png, 8, 4))[1] + 4;
    return substr($png, 0, $pos) . $chunks . substr($png, $pos);
}

/**
 * Adds an ICC profile (ICCP chunk) to a WebP file, converting a simple WebP to the extended
 * format (VP8X) when needed. Returns the new bytes, or null when the file is not understood.
 */
function _ral_img_webp_add_icc($webp, $icc, $w, $h)
{
    if ($icc === '' || substr($webp, 0, 4) !== 'RIFF' || substr($webp, 8, 4) !== 'WEBP') {
        return $icc === '' ? $webp : null;
    }
    $chunks = substr($webp, 12);
    $first = substr($chunks, 0, 4);
    $iccChunk = 'ICCP' . pack('V', strlen($icc)) . $icc . (strlen($icc) % 2 ? "\0" : '');
    if ($first === 'VP8X') {
        $flags = ord($chunks[8]) | 0x20;
        $vp8x = substr($chunks, 0, 8) . chr($flags) . substr($chunks, 9, 9);
        if (strpos(substr($chunks, 18), 'ICCP') === 0) {
            return null;                                // already has a profile
        }
        $body = $vp8x . $iccChunk . substr($chunks, 18);
    } elseif ($first === 'VP8 ' || $first === 'VP8L') {
        $flags = 0x20;
        if ($first === 'VP8L' && strlen($chunks) >= 13 && ord($chunks[8]) === 0x2F) {
            $bits = unpack('V', substr($chunks, 9, 4))[1];
            if (($bits >> 28) & 1) {
                $flags |= 0x10;                         // lossless bitstream uses alpha
            }
        }
        $wm = $w - 1;
        $hm = $h - 1;
        $vp8x = 'VP8X' . pack('V', 10) . chr($flags) . "\0\0\0"
            . chr($wm & 0xFF) . chr(($wm >> 8) & 0xFF) . chr(($wm >> 16) & 0xFF)
            . chr($hm & 0xFF) . chr(($hm >> 8) & 0xFF) . chr(($hm >> 16) & 0xFF);
        $body = $vp8x . $iccChunk . $chunks;
    } else {
        return null;
    }
    return 'RIFF' . pack('V', 4 + strlen($body)) . 'WEBP' . $body;
}

/** Encodes $im as WebP bytes (lossless for images with transparency, lossy otherwise). */
function _ral_img_webp_bytes($im, $lossless)
{
    ob_start();
    try {
        $lossy = RAL_IMG_WEBP_QUALITY;
        $ok = imagewebp($im, null, $lossless ? (defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : 100) : $lossy);
        $bytes = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    if (!$ok || $bytes === false || $bytes === '') {
        throw new RuntimeException('imagewebp failed');
    }
    return $bytes;
}

/** Writes $bytes to $target through a temporary file in the same folder. */
function _ral_img_write_atomic($target, $bytes)
{
    $dir = dirname($target);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('cannot create ' . $dir);
    }
    $tmp = $target . '.tmp' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $bytes) !== strlen($bytes)) {
        @unlink($tmp);
        throw new RuntimeException('cannot write ' . $tmp);
    }
    if (!rename($tmp, $target)) {                       // replaces an existing $target
        @unlink($tmp);
        throw new RuntimeException('cannot rename ' . $tmp);
    }
}

/* ─── PUBLIC: DOWNSIZE ────────────────────────────────────────── */

/**
 * Downsizes and recompresses an original in place, same name and format:
 * longest edge at most 1920 px, EXIF orientation applied, JPEG quality 82 progressive,
 * metadata dropped except the colour profile. The new file replaces the original only when
 * it is smaller. PNGs are rewritten only when they need resizing.
 * Returns true when the file was rewritten, false otherwise (unchanged, unsupported, or error).
 */
function ral_img_downsize($path)
{
    return _ral_img_guard('downsize ' . $path, function () use ($path) {
        if (!_ral_img_gd_ok() || !is_file($path)) {
            return false;
        }
        clearstatcache(true, $path);
        $img = _ral_img_inspect($path);
        if ($img === null) {
            return false;
        }
        $isJpeg = ($img['type'] === IMAGETYPE_JPEG);
        $needsResize = max($img['w'], $img['h']) > RAL_IMG_MAX_EDGE;
        if (!$isJpeg && !$needsResize) {
            return false;                               // PNG within limits: leave it as it is
        }
        if (!_ral_img_memory_ok($img['w'], $img['h'])) {
            error_log('[image_optimizer] downsize skipped (not enough memory): ' . $path);
            return false;
        }
        _ral_img_extend_time_limit();

        $im = _ral_img_load($path, $img);
        list($nw, $nh) = _ral_img_fit($img['w'], $img['h'], RAL_IMG_MAX_EDGE);
        if ($nw !== $img['w'] || $nh !== $img['h']) {
            $im = _ral_img_resize($im, $nw, $nh);
        }
        $im = _ral_img_orient($im, $img['orientation']);

        ob_start();
        try {
            if ($isJpeg) {
                imageinterlace($im, true);
                $ok = imagejpeg($im, null, RAL_IMG_JPEG_QUALITY);
            } else {
                imagesavealpha($im, (bool) $img['alpha']);   // an opaque PNG stays without alpha channel
                $ok = imagepng($im, null, 9);
            }
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($im);
        }
        if (!$ok || $bytes === false || $bytes === '') {
            throw new RuntimeException('encoder failed');
        }
        $bytes = $isJpeg ? _ral_img_jpeg_add_icc($bytes, $img['icc_segments'])
                         : _ral_img_png_add_chunks($bytes, $img['color_chunks']);

        clearstatcache(true, $path);
        if (strlen($bytes) >= filesize($path)) {
            return false;                               // not smaller: keep the original
        }
        _ral_img_write_atomic($path, $bytes);
        clearstatcache(true, $path);
        return true;
    });
}

/* ─── PUBLIC: MIRROR ──────────────────────────────────────────── */

/** True when the mirror of $path is complete and not older than the original. */
function _ral_img_mirror_current($path, array $p, array $img)
{
    $src = filemtime($path);
    $full = is_file($p['full']) ? $p['full'] : (is_file($p['none']) ? $p['none'] : null);
    if ($full === null || filemtime($full) < $src) {
        return false;
    }
    $wantThumb = $img['dw'] > RAL_IMG_THUMB_WIDTH;
    if ($wantThumb !== is_file($p['thumb'])) {
        return false;
    }
    return !$wantThumb || filemtime($p['thumb']) >= $src;
}

/** Removes the mirror files of an original. Returns true when none is left. */
function _ral_img_unlink_mirror(array $p)
{
    $ok = true;
    foreach ($p as $file) {
        if (file_exists($file) && !unlink($file)) {
            $ok = false;
        }
    }
    return $ok;
}

/**
 * Writes or refreshes the WebP mirror (and the 640 px thumbnail) of an original under img/.
 * Without $force it does nothing when the mirror is already up to date. The old mirror is
 * removed first, so a failure leaves no mirror (the original is then served) rather than a stale one.
 * Returns true when the mirror is up to date afterwards.
 */
function ral_img_write_mirror($path, $force = false)
{
    return _ral_img_guard('mirror ' . $path, function () use ($path, $force) {
        $rel = _ral_img_rel($path);
        if ($rel === null || !_ral_img_is_mirrorable($rel)) {
            return false;
        }
        $p = _ral_img_mirror_paths($rel);
        clearstatcache();
        if (!is_file($path)) {
            _ral_img_unlink_mirror($p);
            return false;
        }
        $img = _ral_img_inspect($path);
        if (!$force && $img !== null && _ral_img_mirror_current($path, $p, $img)) {
            return true;
        }
        _ral_img_unlink_mirror($p);
        if ($img === null || !_ral_img_gd_ok() || !function_exists('imagewebp')) {
            return false;                               // not something GD may convert: no mirror
        }
        if (!_ral_img_memory_ok($img['w'], $img['h'])) {
            error_log('[image_optimizer] mirror skipped (not enough memory): ' . $path);
            return false;
        }
        _ral_img_extend_time_limit();

        $im = _ral_img_orient(_ral_img_load($path, $img), $img['orientation']);
        $w = imagesx($im);
        $h = imagesy($im);
        $lossless = $img['alpha'];
        try {
            // Full size: kept only when smaller than the original.
            $full = _ral_img_webp_bytes($im, $lossless);
            $full = _ral_img_webp_add_icc($full, $img['icc'], $w, $h);
            if ($full === null) {
                throw new RuntimeException('could not embed the colour profile');
            }
            clearstatcache(true, $path);
            if (strlen($full) < filesize($path)) {
                _ral_img_write_atomic($p['full'], $full);
            } else {
                _ral_img_write_atomic($p['none'], '');
            }
            unset($full);

            // 640 px wide thumbnail, for srcset.
            if ($w > RAL_IMG_THUMB_WIDTH) {
                $th = max(1, (int) round($h * RAL_IMG_THUMB_WIDTH / $w));
                $thumb = _ral_img_resize($im, RAL_IMG_THUMB_WIDTH, $th, false);
                try {
                    $bytes = _ral_img_webp_bytes($thumb, $lossless);
                } finally {
                    imagedestroy($thumb);
                }
                $bytes = _ral_img_webp_add_icc($bytes, $img['icc'], RAL_IMG_THUMB_WIDTH, $th);
                if ($bytes === null) {
                    throw new RuntimeException('could not embed the colour profile (thumbnail)');
                }
                _ral_img_write_atomic($p['thumb'], $bytes);
            }
        } catch (Throwable $e) {
            _ral_img_unlink_mirror($p);                 // never leave half a mirror behind
            throw $e;
        } finally {
            imagedestroy($im);
        }
        return true;
    });
}

/** Deletes the mirror files of an original (call after deleting or replacing it). */
function ral_img_delete_mirror($path)
{
    return _ral_img_guard('delete mirror ' . $path, function () use ($path) {
        $rel = _ral_img_rel($path);
        if ($rel === null || !_ral_img_is_mirrorable($rel)) {
            return false;
        }
        clearstatcache();
        return _ral_img_unlink_mirror(_ral_img_mirror_paths($rel));
    });
}

/** Deletes a folder under img/_opt/ with everything in it. */
function _ral_img_rmtree($dir)
{
    $root = ral_img_root();
    $norm = str_replace('\\', '/', $dir);
    if ($root === null || stripos($norm, $root . '/_opt/') !== 0 || !is_dir($dir)) {
        return;                                         // only ever deletes inside img/_opt/
    }
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $full = $dir . '/' . $f;
        if (is_dir($full) && !is_link($full)) {
            _ral_img_rmtree($full);
        } else {
            unlink($full);
        }
    }
    rmdir($dir);
}

/**
 * Moves the mirror folder of a renamed gallery folder (call after the original folder was renamed).
 * If the move fails, the old mirror folder is deleted and the mirror is rebuilt for the new folder.
 */
function ral_img_rename_mirror_dir($oldDir, $newDir)
{
    return _ral_img_guard('rename mirror ' . $oldDir . ' -> ' . $newDir, function () use ($oldDir, $newDir) {
        $oldRel = _ral_img_rel($oldDir);
        $newRel = _ral_img_rel($newDir);
        if ($oldRel === null || $newRel === null) {
            return false;
        }
        $root = ral_img_root();
        $oldM = $root . '/_opt/' . $oldRel;
        $newM = $root . '/_opt/' . $newRel;
        clearstatcache();
        if (!is_dir($oldM)) {
            return true;
        }
        if (is_dir($newM)) {
            _ral_img_rmtree($newM);                     // stale mirror of an older folder with that name
        }
        if (!is_dir(dirname($newM))) {
            mkdir(dirname($newM), 0777, true);
        }
        if (@rename($oldM, $newM)) {
            return true;
        }
        _ral_img_rmtree($oldM);
        $ok = true;
        if (is_dir($newDir)) {
            foreach (scandir($newDir) as $f) {
                $full = rtrim($newDir, '/\\') . '/' . $f;
                if (is_file($full) && _ral_img_is_mirrorable($f)) {
                    $ok = ral_img_write_mirror($full, true) && $ok;
                }
            }
        }
        return $ok;
    });
}

/**
 * For upload code: downsizes a freshly written image in place, then rebuilds its mirror.
 * Safe to call for any file (GIF/WebP and other files are left alone).
 */
function ral_img_process_upload($path)
{
    return _ral_img_guard('process upload ' . $path, function () use ($path) {
        if (!_ral_img_is_mirrorable($path)) {
            return false;
        }
        ral_img_downsize($path);
        return ral_img_write_mirror($path, true);
    });
}

/* ─── PUBLIC: URLS FOR PAGES ──────────────────────────────────── */

/**
 * Extra fields for a lab card (helperFiles/laboratory-card.js): imageWebp, imageWebp640,
 * imageOriginal, imageWidth, imageHeight. Returns [] when the image has no mirror (the card then
 * renders the plain <img> it always did). Never throws.
 */
function ral_img_card_fields($relPath)
{
    $info = ral_img_mirror_info($relPath);
    if (!is_array($info)) {
        return [];
    }
    return [
        'imageWebp'     => $info['webp'],
        'imageWebp640'  => $info['webp640'],
        'imageOriginal' => $info['original'],
        'imageWidth'    => $info['width'],
        'imageHeight'   => $info['height'],
    ];
}

/**
 * Mirror URLs for an original given relative to the project root (as stored in the database,
 * e.g. "img/labimages/Lab 1.jpg"). Returns null when there is no mirror, otherwise
 * ['webp' => URL of the full-size WebP or null, 'webp640' => URL of the 640 px WebP or null,
 *  'width' => displayed width, 'height' => displayed height, 'original' => URL of the original].
 * Every URL is percent-encoded per path segment (safe inside srcset) and carries ?v=filemtime.
 */
function ral_img_mirror_info($relPath)
{
    return _ral_img_guard('mirror info ' . $relPath, function () use ($relPath) {
        $project = dirname(__DIR__);
        $path = $project . '/' . ltrim(str_replace('\\', '/', (string) $relPath), '/');
        $rel = _ral_img_rel($path);
        if ($rel === null || !_ral_img_is_mirrorable($rel) || !is_file($path)) {
            return null;
        }
        $p = _ral_img_mirror_paths($rel);
        $hasFull = is_file($p['full']);
        $hasThumb = is_file($p['thumb']);
        if (!$hasFull && !$hasThumb) {
            return null;
        }
        $img = _ral_img_inspect($path);
        if ($img === null) {
            return null;
        }
        $url = function ($file, $relUrl) {
            $enc = implode('/', array_map('rawurlencode', explode('/', $relUrl)));
            return $enc . '?v=' . filemtime($file);
        };
        return [
            'webp'     => $hasFull ? $url($p['full'], 'img/_opt/' . $rel . '.webp') : null,
            'webp640'  => $hasThumb ? $url($p['thumb'], 'img/_opt/' . $rel . '.640w.webp') : null,
            'width'    => $img['dw'],
            'height'   => $img['dh'],
            'original' => $url($path, 'img/' . $rel),
        ];
    });
}
