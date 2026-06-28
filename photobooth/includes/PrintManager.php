<?php
/**
 * includes/PrintManager.php
 * ------------------------------------------------------------------
 * A small abstraction over "send this file to a printer". Real kiosk
 * deployments vary wildly (CUPS on Linux/macOS, the Windows spooler,
 * vendor SDKs for dye-sub printers) so this class focuses on doing the
 * common case well — shelling out to the OS print command — while
 * degrading gracefully to a "simulated" print (clearly logged) when no
 * printer is configured or the print command isn't available. This
 * keeps the rest of the app (and the demo) fully functional without a
 * physical printer attached.
 *
 * All printing-related settings (auto print, printer name, paper size,
 * copies, margins, scale) come from Settings — never hard-coded.
 * ------------------------------------------------------------------
 */
class PrintManager
{
    /**
     * Print (or simulate printing) a single image file according to the
     * current settings. Returns a result array that callers persist onto
     * the session's metadata.json via SessionManager::recordPrintStatus().
     */
    public static function printFile(string $absoluteImagePath): array
    {
        $printing = Settings::get('printing', []);
        $copies = max(1, (int)($printing['copies'] ?? 1));
        $printer = trim($printing['printer_name'] ?? '');
        $paperSize = $printing['paper_size'] ?? '4x6';
        $marginsMm = (float)($printing['margins_mm'] ?? 0);
        $scalePercent = (int)($printing['scale_percent'] ?? 100);

        if (!is_file($absoluteImagePath)) {
            return ['status' => 'failed', 'reason' => 'File not found: ' . $absoluteImagePath];
        }

        // No printer configured -> simulate (kiosk still works without hardware).
        if ($printer === '' || !self::printCommandAvailable()) {
            return [
                'status' => 'simulated',
                'reason' => $printer === ''
                    ? 'No printer configured in Settings — simulated print.'
                    : 'No system print command (lp/lpr) available — simulated print.',
                'copies' => $copies,
                'paper_size' => $paperSize,
            ];
        }

        $cmd = self::buildPrintCommand($absoluteImagePath, $printer, $copies, $paperSize, $marginsMm, $scalePercent);
        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode === 0) {
            return [
                'status' => 'printed',
                'printer' => $printer,
                'copies' => $copies,
                'paper_size' => $paperSize,
                'command' => $cmd,
            ];
        }

        return [
            'status' => 'failed',
            'reason' => 'Print command exited with code ' . $exitCode,
            'output' => implode("\n", $output),
            'command' => $cmd,
        ];
    }

    /** True if a CUPS-style print command exists on this system (Linux/macOS). */
    private static function printCommandAvailable(): bool
    {
        $which = trim((string)@shell_exec('command -v lp 2>/dev/null'));
        return $which !== '';
    }

    /** Build a safe `lp` invocation from settings. Windows kiosks should swap this for SumatraPDF /print or a vendor CLI. */
    private static function buildPrintCommand(
        string $file,
        string $printer,
        int $copies,
        string $paperSize,
        float $marginsMm,
        int $scalePercent
    ): string {
        $mediaMap = [
            '4x6' => 'Custom.4x6in',
            '5x7' => 'Custom.5x7in',
            '2x6' => 'Custom.2x6in',
            'A4' => 'A4',
            'Letter' => 'Letter',
        ];
        $media = $mediaMap[$paperSize] ?? $paperSize;

        $parts = [
            'lp',
            '-d', escapeshellarg($printer),
            '-n', escapeshellarg((string)$copies),
            '-o', escapeshellarg('media=' . $media),
            '-o', escapeshellarg('fit-to-page'),
        ];
        if ($marginsMm > 0) {
            // CUPS expects points (1mm ~= 2.83465pt) for custom page margins.
            $pt = (int)round($marginsMm * 2.83465);
            $parts[] = '-o';
            $parts[] = escapeshellarg("page-left={$pt} page-right={$pt} page-top={$pt} page-bottom={$pt}");
        }
        if ($scalePercent !== 100) {
            $parts[] = '-o';
            $parts[] = escapeshellarg('scaling=' . max(1, min(1000, $scalePercent)));
        }
        $parts[] = escapeshellarg($file);

        return implode(' ', $parts);
    }

    /**
     * Best-effort enumeration of installed printers (for the Settings page
     * dropdown). Returns an empty array if the system has no CUPS/lpstat —
     * the admin can still type a printer name manually.
     */
    public static function listPrinters(): array
    {
        if (trim((string)@shell_exec('command -v lpstat 2>/dev/null')) === '') {
            return [];
        }
        $raw = (string)@shell_exec('lpstat -p 2>/dev/null');
        $printers = [];
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/^printer\s+(\S+)/', $line, $m)) {
                $printers[] = $m[1];
            }
        }
        return $printers;
    }
}
