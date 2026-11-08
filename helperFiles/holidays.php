<?php
/*
 * Days the labs are closed besides Sundays. Returns ['YYYY-MM-DD' => 'Name'] for the given years.
 * Fixed-date holidays repeat every year; movable ones (Holy Week, Eid, National Heroes Day) must be
 * added to $movable each year.
 */
function ral_holidays(array $years): array
{
    $fixed = [
        '01-01' => "New Year's Day",
        '04-09' => 'Araw ng Kagitingan',
        '05-01' => 'Labor Day',
        '06-12' => 'Independence Day',
        '08-21' => 'Ninoy Aquino Day',
        '11-01' => "All Saints' Day",
        '11-30' => 'Bonifacio Day',
        '12-08' => 'Feast of the Immaculate Conception',
        '12-25' => 'Christmas Day',
        '12-30' => 'Rizal Day',
        '12-31' => "New Year's Eve",
    ];
    $movable = [
        '2026-03-20' => "Eid'l Fitr",
        '2026-04-02' => 'Maundy Thursday',
        '2026-04-03' => 'Good Friday',
        '2026-05-27' => "Eid'l Adha",
        '2026-08-31' => 'National Heroes Day',
    ];
    $out = [];
    foreach ($years as $y) {
        foreach ($fixed as $md => $name) $out["$y-$md"] = $name;
    }
    return $out + $movable;
}

/* ---- No-class days set by admins (no_class_days.php) ---- */

/** Create scilab_no_class_days on first use. */
function ral_ensure_no_class_table(mysqli $conn): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `scilab_no_class_days` (
            `noClassDate` date NOT NULL,
            `reason` varchar(100) NOT NULL DEFAULT '',
            `createdBy` varchar(100) NOT NULL DEFAULT '',
            `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`noClassDate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch (Throwable $e) { /* never take a page down over this */ }
}

/** Admin-set no-class days: ['YYYY-MM-DD' => reason]. */
function ral_no_class_days(mysqli $conn): array
{
    ral_ensure_no_class_table($conn);
    $out = [];
    try {
        $res = $conn->query("SELECT noClassDate, reason FROM scilab_no_class_days ORDER BY noClassDate");
        while ($res && ($row = $res->fetch_assoc())) {
            $out[$row['noClassDate']] = $row['reason'] !== '' ? $row['reason'] : 'No classes';
        }
    } catch (Throwable $e) {}
    return $out;
}

/** Every closed date the picker should grey out (holidays + admin no-class days), over the next few years. */
function ral_closed_dates(mysqli $conn): array
{
    $y = (int) date('Y');
    return ral_no_class_days($conn) + ral_holidays([$y, $y + 1, $y + 2]);
}

/** Reason a date can't be booked ('' if it can): Sunday, holiday or admin no-class day. */
function ral_closed_reason(mysqli $conn, string $date): string
{
    $days = ral_closed_dates($conn);
    if (isset($days[$date])) return $days[$date];
    return date('w', strtotime($date)) === '0' ? 'Sunday' : '';
}
