<?php
/**
 * "Printed" mark on approved requests (migration sql/2026-11-13_05_print_status.sql).
 *
 * The lab prints the approved form; an admin then marks the request as printed so the
 * Approved list shows what is still left to print. printedAt NULL = not printed.
 */

if (!function_exists('scilab_ensure_print_columns')) {
    /**
     * Add printedAt / printedBy to scilab_form_requests if the migration was not run.
     * One SHOW COLUMNS per PHP request; never throws.
     */
    function scilab_ensure_print_columns($conn)
    {
        static $checked = false;
        if ($checked || !($conn instanceof mysqli)) return;
        $checked = true;

        try {
            $res = $conn->query("SHOW COLUMNS FROM `scilab_form_requests` LIKE 'printedAt'");
            if ($res && $res->num_rows > 0) return;
            $conn->query("ALTER TABLE `scilab_form_requests`
                ADD COLUMN IF NOT EXISTS `printedAt` datetime     NULL DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS `printedBy` varchar(255) NULL DEFAULT NULL");
        } catch (Throwable $e) {
            error_log('printedAt/printedBy could not be added: ' . $e->getMessage());
        }
    }
}

if (!function_exists('scilab_set_printed')) {
    /**
     * Mark (or unmark) an approved request as printed.
     * @return array ['ok' => bool, 'message' => string, 'printedAt' => ?string, 'printedBy' => ?string]
     */
    function scilab_set_printed($conn, $formID, $printed, $adminName)
    {
        scilab_ensure_print_columns($conn);
        $formID = intval($formID);

        $stmt = $conn->prepare("SELECT statusScilabPersonnel FROM scilab_form_requests WHERE id = ?");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$req) return ['ok' => false, 'message' => 'Request not found.'];
        if (strtolower(trim((string)$req['statusScilabPersonnel'])) !== 'approved') {
            return ['ok' => false, 'message' => 'Only an approved request can be marked as printed.'];
        }

        if ($printed) {
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET printedAt = NOW(), printedBy = ? WHERE id = ?");
            $stmt->bind_param("si", $adminName, $formID);
        } else {
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET printedAt = NULL, printedBy = NULL WHERE id = ?");
            $stmt->bind_param("i", $formID);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT printedAt, printedBy FROM scilab_form_requests WHERE id = ?");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'ok' => true,
            'message' => $printed ? 'Marked as printed.' : 'Printed mark removed.',
            'printedAt' => $row['printedAt'] ?? null,
            'printedBy' => $row['printedBy'] ?? null,
        ];
    }
}
