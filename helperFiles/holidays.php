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
