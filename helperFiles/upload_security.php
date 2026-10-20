<?php
/**
 * Upload and file-path safety helpers.
 *
 * An upload is trusted only after ral_validate_image_upload() passes: the
 * client's file name and declared type are never used to decide anything.
 */

/** Accepted image types: detected MIME type => extension the file is stored with. */
const RAL_UPLOAD_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
const RAL_UPLOAD_MAX_BYTES  = 15 * 1024 * 1024;   // per file
const RAL_UPLOAD_MAX_PIXELS = 50000000;           // guards against decompression bombs

if (!function_exists('ral_validate_image_upload')) {

    /** Why PHP rejected an upload, in words for the user. */
    function ral_upload_error_message($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The image is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'The image was only partly uploaded. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No image was selected.';
            default:
                return 'Image upload failed.';
        }
    }

    /**
     * Check one uploaded file ($_FILES entry shape: name, tmp_name, error, size).
     *
     * @param array $allowed detected MIME type => extension (default: jpeg, png, webp)
     * @return array ['ok' => true, 'ext' => 'jpg', 'mime' => 'image/jpeg']
     *               or ['ok' => false, 'error' => 'message']
     */
    function ral_validate_image_upload(array $file, array $allowed = RAL_UPLOAD_IMAGE_TYPES)
    {
        $fail = function ($msg) {
            return ['ok' => false, 'error' => $msg];
        };

        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            return $fail(ral_upload_error_message($err));
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
            return $fail('Image upload failed.');
        }
        $size = filesize($tmp);
        if ($size === false || $size <= 0) {
            return $fail('The uploaded image is empty.');
        }
        if ($size > RAL_UPLOAD_MAX_BYTES) {
            return $fail('The image is too large (limit ' . (RAL_UPLOAD_MAX_BYTES / 1048576) . ' MB).');
        }

        // What the bytes are, not what the client says they are.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!is_string($mime) || !isset($allowed[$mime])) {
            return $fail('Only ' . strtoupper(implode('/', array_values(array_unique($allowed)))) . ' images are allowed.');
        }

        // The image header must parse, agree with the MIME type, and be a sane size.
        $info = @getimagesize($tmp);
        if ($info === false || image_type_to_mime_type($info[2]) !== $mime) {
            return $fail('The file is not a valid image.');
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > RAL_UPLOAD_MAX_PIXELS) {
            return $fail('The image dimensions are not supported.');
        }

        // A script hidden inside an otherwise valid image (polyglot file).
        $body = file_get_contents($tmp);
        if ($body === false || stripos($body, '<?php') !== false) {
            return $fail('The file is not a valid image.');
        }

        return ['ok' => true, 'ext' => $allowed[$mime], 'mime' => $mime];
    }

    /** Lab name reduced to the characters allowed in a folder or file name. */
    function ral_lab_folder_name($labName)
    {
        return trim(preg_replace('/[^a-zA-Z0-9 \-_]/', '', (string)$labName));
    }

    /** A client file name reduced to a safe base name (no dots, no path), or '' if nothing is left. */
    function ral_safe_file_base($fileName, $maxLen = 80)
    {
        $base = pathinfo(basename(str_replace('\\', '/', (string)$fileName)), PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
        return substr(trim($base, '_-'), 0, $maxLen);
    }

    /** First free "<dir><base>.<ext>", then "<base>_2.<ext>", and so on. */
    function ral_unique_upload_path($dir, $base, $ext)
    {
        $path = $dir . $base . '.' . $ext;
        for ($n = 2; file_exists($path); $n++) {
            $path = $dir . $base . '_' . $n . '.' . $ext;
        }
        return $path;
    }

    /** True when $path (which must exist) is inside $root after symlinks and ".." are resolved. */
    function ral_path_within($path, $root)
    {
        $realPath = realpath($path);
        $realRoot = realpath($root);
        if ($realPath === false || $realRoot === false) {
            return false;
        }
        $realRoot = rtrim($realRoot, '/\\') . DIRECTORY_SEPARATOR;
        return strncmp($realPath . DIRECTORY_SEPARATOR, $realRoot, strlen($realRoot)) === 0;
    }
}
