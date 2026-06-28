<?php
/**
 * includes/SessionManager.php
 * ------------------------------------------------------------------
 * Every photobooth run ("session") gets its own timestamped folder:
 *
 *   sessions/
 *     2026-06-28_14-05-02/
 *       raw/photo1.jpg .. photo4.jpg
 *       final/strip.png
 *       metadata.json
 *
 * No database — metadata.json IS the record. SessionManager only
 * touches the filesystem; it knows nothing about cameras, compositing
 * or printing (those are separate classes), keeping responsibilities
 * cleanly separated as required by the architecture brief.
 * ------------------------------------------------------------------
 */
class SessionManager
{
    /** Create a brand-new session folder and an initial metadata.json. */
    public static function create(string $templateId): array
    {
        $id = make_session_timestamp();
        // Guard against a same-second collision (rapid testing, fast taps).
        while (is_dir(SESSIONS_PATH . '/' . $id)) {
            usleep(250000);
            $id = make_session_timestamp();
        }

        $dir = SESSIONS_PATH . '/' . $id;
        mkdir($dir . '/raw', 0775, true);
        mkdir($dir . '/final', 0775, true);

        $metadata = [
            'session_id' => $id,
            'created_at' => date('c'),
            'template' => $templateId,
            'photos' => [],
            'strip' => null,
            'print_status' => 'not_printed',
            'settings_snapshot' => Settings::all(),
        ];
        write_json_file($dir . '/metadata.json', $metadata);

        return ['session_id' => $id, 'dir' => $dir];
    }

    /** Path helpers ----------------------------------------------------- */
    public static function dir(string $sessionId): string
    {
        return SESSIONS_PATH . '/' . sanitize_id($sessionId);
    }

    public static function rawDir(string $sessionId): string
    {
        return self::dir($sessionId) . '/raw';
    }

    public static function finalDir(string $sessionId): string
    {
        return self::dir($sessionId) . '/final';
    }

    public static function metadataPath(string $sessionId): string
    {
        return self::dir($sessionId) . '/metadata.json';
    }

    /** Save one captured raw photo (binary JPEG bytes) as photo<N>.jpg. */
    public static function savePhoto(string $sessionId, int $index, string $binaryJpeg): string
    {
        $path = self::rawDir($sessionId) . "/photo{$index}.jpg";
        file_put_contents($path, $binaryJpeg);

        $meta = self::getMetadata($sessionId);
        $meta['photos'][$index] = "raw/photo{$index}.jpg";
        ksort($meta['photos']);
        self::saveMetadata($sessionId, $meta);

        return $path;
    }

    /** Read metadata.json for a session. */
    public static function getMetadata(string $sessionId): array
    {
        return read_json_file(self::metadataPath($sessionId), []);
    }

    /** Persist metadata.json for a session (merging onto the existing record). */
    public static function saveMetadata(string $sessionId, array $metadata): bool
    {
        return write_json_file(self::metadataPath($sessionId), $metadata);
    }

    /** Mark the final strip path + template used once compositing succeeds. */
    public static function recordStrip(string $sessionId, string $relativeStripPath): void
    {
        $meta = self::getMetadata($sessionId);
        $meta['strip'] = $relativeStripPath;
        $meta['composited_at'] = date('c');
        self::saveMetadata($sessionId, $meta);
    }

    /** Record print outcome (success/failure/skipped) onto metadata.json. */
    public static function recordPrintStatus(string $sessionId, string $status, array $extra = []): void
    {
        $meta = self::getMetadata($sessionId);
        $meta['print_status'] = $status;
        $meta['print_info'] = array_merge($extra, ['attempted_at' => date('c')]);
        self::saveMetadata($sessionId, $meta);
    }

    /**
     * List every session, newest first — used by the admin Gallery.
     * @return array<int, array<string,mixed>>
     */
    public static function listAll(): array
    {
        if (!is_dir(SESSIONS_PATH)) {
            return [];
        }
        $entries = scandir(SESSIONS_PATH);
        $sessions = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = SESSIONS_PATH . '/' . $entry;
            if (!is_dir($dir) || !is_file($dir . '/metadata.json')) {
                continue;
            }
            $meta = read_json_file($dir . '/metadata.json', []);
            $meta['session_id'] = $meta['session_id'] ?? $entry;
            $meta['strip_url'] = $meta['strip'] ? 'sessions/' . $entry . '/' . $meta['strip'] : null;
            $sessions[] = $meta;
        }
        // Newest first (folder names are timestamp-sortable strings).
        usort($sessions, fn($a, $b) => strcmp($b['session_id'], $a['session_id']));
        return $sessions;
    }
}
