<?php
/**
 * get_requests.php
 * ─────────────────────────────────────────────────────────────────
 * Server-side data API for lab requests table.
 * Handles: search, sort, pagination using mock data.
 *
 * PARAMS (GET)
 *   page          int    Current page (1-based)
 *   pageSize      int    Rows per page
 *   search        string Filter string
 *   sortColumn    string Column key to sort by
 *   sortDirection string "asc" | "desc"
 *
 * RESPONSE JSON
 *   { data:[...], total:int, page:int, pageSize:int }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');   // Adjust for production

/* ─── Input Sanitisation ─────────────────────────────────────── */

$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = max(1, min(200, (int) ($_GET['pageSize'] ?? 10)));
$search = trim(strtolower($_GET['search'] ?? ''));
$sortCol = $_GET['sortColumn'] ?? 'id';
$sortDir = strtolower($_GET['sortDirection'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

/* Allowed sort columns — prevents arbitrary key injection */
$allowedSort = [
    'id',
    'requester_name',
    'lab_name',
    'grade_section',
    'subject',
    'datetime_use',
    'teacher_supervisor',
    'status',
];
if (!in_array($sortCol, $allowedSort, true)) {
    $sortCol = 'id';
}

/* ─── Mock Dataset ───────────────────────────────────────────── */
/* In production, replace with PDO/MySQLi query + LIMIT/OFFSET.   */

$allData = [
    [
        'id' => 1001,
        'requester_name' => 'Maria Santos',
        'lab_name' => 'Biology Lab A',
        'grade_section' => 'Grade 11 – STEM-A',
        'subject' => 'Biology',
        'datetime_use' => '2025-05-12 08:00',
        'materials' => ['Microscope', 'Glass slides', 'Coverslips', 'Iodine solution', 'Forceps'],
        'teacher_supervisor' => 'Mr. dela Cruz',
        'status' => 'Approved',
    ],
    [
        'id' => 1002,
        'requester_name' => 'Juan Reyes',
        'lab_name' => 'Chemistry Lab B',
        'grade_section' => 'Grade 12 – STEM-B',
        'subject' => 'Chemistry',
        'datetime_use' => '2025-05-13 10:00',
        'materials' => ['Beakers', 'Bunsen burner', 'Sodium chloride', 'Litmus paper'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Pending',
    ],
    [
        'id' => 1003,
        'requester_name' => 'Ana Lim',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 12 – STEM-C',
        'subject' => 'Physics',
        'datetime_use' => '2025-05-14 13:00',
        'materials' => ['Voltmeter', 'Resistors', 'Battery pack', 'Connecting wires', 'Breadboard', 'Multimeter'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Under Review',
    ],
    [
        'id' => 1004,
        'requester_name' => 'Carlos Mendoza',
        'lab_name' => 'Biology Lab A',
        'grade_section' => 'Grade 11 – GAS-A',
        'subject' => 'Earth Science',
        'datetime_use' => '2025-05-15 08:30',
        'materials' => ['Rock samples', 'Magnifying glass', 'Hardness kit'],
        'teacher_supervisor' => 'Ms. Ramos',
        'status' => 'Rejected',
    ],
    [
        'id' => 1005,
        'requester_name' => 'Sofia Villanueva',
        'lab_name' => 'Chemistry Lab A',
        'grade_section' => 'Grade 11 – STEM-B',
        'subject' => 'General Chemistry',
        'datetime_use' => '2025-05-15 14:00',
        'materials' => ['Test tubes', 'Pipette', 'Hydrochloric acid', 'Safety goggles', 'Lab gloves'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Pending',
    ],
    [
        'id' => 1006,
        'requester_name' => 'Miguel Torres',
        'lab_name' => 'Computer Lab 1',
        'grade_section' => 'Grade 12 – ICT-A',
        'subject' => 'Programming',
        'datetime_use' => '2025-05-16 09:00',
        'materials' => ['USB drives', 'Network cables', 'Patch panel tools'],
        'teacher_supervisor' => 'Mr. Ocampo',
        'status' => 'Approved',
    ],
    [
        'id' => 1007,
        'requester_name' => 'Gabriela Cruz',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 11 – STEM-D',
        'subject' => 'Physics',
        'datetime_use' => '2025-05-16 11:00',
        'materials' => ['Spring scales', 'Pulleys', 'Mass sets'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Force Approved',
    ],
    [
        'id' => 1008,
        'requester_name' => 'Paolo Fernandez',
        'lab_name' => 'Biology Lab B',
        'grade_section' => 'Grade 12 – STEM-A',
        'subject' => 'Genetics',
        'datetime_use' => '2025-05-17 08:00',
        'materials' => ['DNA extraction kit', 'Micropipettes', 'Centrifuge tubes', 'Electrophoresis gel'],
        'teacher_supervisor' => 'Dr. Aquino',
        'status' => 'Pending',
    ],
    [
        'id' => 1009,
        'requester_name' => 'Isabelle Navarro',
        'lab_name' => 'Chemistry Lab B',
        'grade_section' => 'Grade 11 – STEM-C',
        'subject' => 'Organic Chemistry',
        'datetime_use' => '2025-05-17 13:00',
        'materials' => ['Round-bottom flask', 'Condenser', 'Ethanol', 'Heat mantle', 'Thermometer'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Under Review',
    ],
    [
        'id' => 1010,
        'requester_name' => 'Rafael Castillo',
        'lab_name' => 'Computer Lab 2',
        'grade_section' => 'Grade 12 – ICT-B',
        'subject' => 'Network Fundamentals',
        'datetime_use' => '2025-05-18 10:00',
        'materials' => ['Cisco routers', 'Console cables', 'UTP cables', 'RJ-45 connectors'],
        'teacher_supervisor' => 'Mr. Ocampo',
        'status' => 'Approved',
    ],
    [
        'id' => 1011,
        'requester_name' => 'Camille Espinosa',
        'lab_name' => 'Biology Lab A',
        'grade_section' => 'Grade 11 – STEM-A',
        'subject' => 'Zoology',
        'datetime_use' => '2025-05-19 08:00',
        'materials' => ['Dissecting kit', 'Preserved specimen', 'Dissecting tray', 'Pins'],
        'teacher_supervisor' => 'Dr. Aquino',
        'status' => 'Pending',
    ],
    [
        'id' => 1012,
        'requester_name' => 'Joshua Flores',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 12 – STEM-B',
        'subject' => 'Modern Physics',
        'datetime_use' => '2025-05-19 14:00',
        'materials' => ['Laser pointer', 'Diffraction grating', 'Optical bench', 'Photodetector'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Rejected',
    ],
    [
        'id' => 1013,
        'requester_name' => 'Danielle Reyes',
        'lab_name' => 'Chemistry Lab A',
        'grade_section' => 'Grade 11 – STEM-D',
        'subject' => 'Biochemistry',
        'datetime_use' => '2025-05-20 09:00',
        'materials' => ['Spectrophotometer', 'Cuvettes', 'Buffer solutions', 'Pipette tips'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Approved',
    ],
    [
        'id' => 1014,
        'requester_name' => 'Marco Villanueva',
        'lab_name' => 'Biology Lab B',
        'grade_section' => 'Grade 12 – STEM-C',
        'subject' => 'Microbiology',
        'datetime_use' => '2025-05-20 11:00',
        'materials' => ['Petri dishes', 'Agar plates', 'Inoculation loop', 'Autoclave bags', 'Bunsen burner', 'Colony counter'],
        'teacher_supervisor' => 'Dr. Aquino',
        'status' => 'Under Review',
    ],
    [
        'id' => 1015,
        'requester_name' => 'Patricia Mendoza',
        'lab_name' => 'Computer Lab 1',
        'grade_section' => 'Grade 11 – ICT-A',
        'subject' => 'Web Development',
        'datetime_use' => '2025-05-21 08:00',
        'materials' => ['USB hubs', 'External drives'],
        'teacher_supervisor' => 'Mr. Ocampo',
        'status' => 'Pending',
    ],
    [
        'id' => 1016,
        'requester_name' => 'Andrei Santos',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 11 – STEM-B',
        'subject' => 'Thermodynamics',
        'datetime_use' => '2025-05-21 13:00',
        'materials' => ['Calorimeter', 'Thermometer', 'Stirring rod', 'Heat lamp'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Force Approved',
    ],
    [
        'id' => 1017,
        'requester_name' => 'Beatrice Lim',
        'lab_name' => 'Chemistry Lab B',
        'grade_section' => 'Grade 12 – STEM-D',
        'subject' => 'Analytical Chemistry',
        'datetime_use' => '2025-05-22 09:30',
        'materials' => ['Burette', 'Conical flask', 'NaOH solution', 'Phenolphthalein indicator'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Approved',
    ],
    [
        'id' => 1018,
        'requester_name' => 'Emmanuel Cruz',
        'lab_name' => 'Biology Lab A',
        'grade_section' => 'Grade 11 – GAS-B',
        'subject' => 'Botany',
        'datetime_use' => '2025-05-22 14:00',
        'materials' => ['Plant specimens', 'Microscope slides', 'Staining kits', 'Scalpel'],
        'teacher_supervisor' => 'Ms. Ramos',
        'status' => 'Pending',
    ],
    [
        'id' => 1019,
        'requester_name' => 'Francesca Torres',
        'lab_name' => 'Computer Lab 2',
        'grade_section' => 'Grade 12 – ICT-C',
        'subject' => 'Database Systems',
        'datetime_use' => '2025-05-23 10:00',
        'materials' => ['Server cables', 'Flash drives'],
        'teacher_supervisor' => 'Mr. Ocampo',
        'status' => 'Approved',
    ],
    [
        'id' => 1020,
        'requester_name' => 'Gerard Navarro',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 12 – STEM-A',
        'subject' => 'Quantum Mechanics',
        'datetime_use' => '2025-05-23 13:00',
        'materials' => ['Prism set', 'Spectrum analyzer', 'Laser kit', 'Slit plate', 'Screen'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Under Review',
    ],
    [
        'id' => 1021,
        'requester_name' => 'Hailey Fernandez',
        'lab_name' => 'Biology Lab B',
        'grade_section' => 'Grade 11 – STEM-C',
        'subject' => 'Cell Biology',
        'datetime_use' => '2025-05-24 08:00',
        'materials' => ['Centrifuge', 'Microtubes', 'Bradford reagent', 'Vortex mixer'],
        'teacher_supervisor' => 'Dr. Aquino',
        'status' => 'Rejected',
    ],
    [
        'id' => 1022,
        'requester_name' => 'Ivan Reyes',
        'lab_name' => 'Chemistry Lab A',
        'grade_section' => 'Grade 12 – STEM-B',
        'subject' => 'Physical Chemistry',
        'datetime_use' => '2025-05-24 11:00',
        'materials' => ['pH meter', 'Electrode set', 'Buffer tablets', 'Conductivity probe'],
        'teacher_supervisor' => 'Ms. Garcia',
        'status' => 'Pending',
    ],
    [
        'id' => 1023,
        'requester_name' => 'Jasmine Castillo',
        'lab_name' => 'Biology Lab A',
        'grade_section' => 'Grade 11 – STEM-B',
        'subject' => 'Anatomy',
        'datetime_use' => '2025-05-25 08:30',
        'materials' => ['Anatomical model', 'Dissection guide', 'Measuring tape'],
        'teacher_supervisor' => 'Dr. Aquino',
        'status' => 'Approved',
    ],
    [
        'id' => 1024,
        'requester_name' => 'Kevin Espinosa',
        'lab_name' => 'Computer Lab 1',
        'grade_section' => 'Grade 12 – ICT-A',
        'subject' => 'Cybersecurity',
        'datetime_use' => '2025-05-25 14:00',
        'materials' => ['Raspberry Pi kits', 'Ethernet cables', 'SD cards', 'USB adapters'],
        'teacher_supervisor' => 'Mr. Ocampo',
        'status' => 'Under Review',
    ],
    [
        'id' => 1025,
        'requester_name' => 'Laura Flores',
        'lab_name' => 'Physics Lab',
        'grade_section' => 'Grade 11 – STEM-A',
        'subject' => 'Electromagnetism',
        'datetime_use' => '2025-05-26 09:00',
        'materials' => ['Bar magnets', 'Iron filings', 'Compass set', 'Solenoid coil', 'DC power supply'],
        'teacher_supervisor' => 'Mr. Bautista',
        'status' => 'Pending',
    ],
];

/* ─── Search / Filter ────────────────────────────────────────── */

if ($search !== '') {
    $allData = array_filter($allData, function ($row) use ($search) {
        /* Search across text-friendly columns */
        $haystack = strtolower(implode(' ', [
            $row['id'],
            $row['requester_name'],
            $row['lab_name'],
            $row['grade_section'],
            $row['subject'],
            $row['datetime_use'],
            $row['teacher_supervisor'],
            $row['status'],
            implode(' ', $row['materials']),
        ]));
        return str_contains($haystack, $search);
    });
    $allData = array_values($allData); // Re-index after filter
}

/* ─── Sort ───────────────────────────────────────────────────── */

usort($allData, function ($a, $b) use ($sortCol, $sortDir) {
    $valA = $a[$sortCol] ?? '';
    $valB = $b[$sortCol] ?? '';

    /* Numeric comparison for the ID column */
    if (is_numeric($valA) && is_numeric($valB)) {
        $cmp = $valA <=> $valB;
    } else {
        $cmp = strcmp(strtolower((string) $valA), strtolower((string) $valB));
    }

    return $sortDir === 'desc' ? -$cmp : $cmp;
});

/* ─── Pagination — OFFSET = (page - 1) × pageSize ───────────── */

$total = count($allData);
$offset = ($page - 1) * $pageSize;
$paged = array_slice($allData, $offset, $pageSize);

/* ─── Response ───────────────────────────────────────────────── */

echo json_encode([
    'data' => array_values($paged),
    'total' => $total,
    'page' => $page,
    'pageSize' => $pageSize,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);