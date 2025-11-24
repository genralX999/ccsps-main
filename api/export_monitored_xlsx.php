<?php
require_once __DIR__ . '/../includes/init.php';

// Composer autoload (PhpSpreadsheet)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    exit("PhpSpreadsheet not installed.");
}
require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

requireAuth();

// Clean any previous output (IMPORTANT!)
while (ob_get_level()) { ob_end_clean(); }

// Build filters
$params = [];
$where = "mi.is_deleted = 0";

if (!empty($_GET['user_id'])) {
    $where .= " AND mi.user_id = :user_id";
    $params[':user_id'] = (int)$_GET['user_id'];
} elseif (!isset($_GET['show_all'])) {
    // default for non-superadmin
    $role = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $role->execute([':id' => $_SESSION['user_id']]);
    if ($role->fetchColumn() !== 'superadmin') {
        $where .= " AND mi.user_id = :current_user";
        $params[':current_user'] = (int)$_SESSION['user_id'];
    }
}

if (!empty($_GET['region_id'])) {
    $where .= " AND mi.region_id = :region_id";
    $params[':region_id'] = (int)$_GET['region_id'];
}

if (!empty($_GET['event_type_id'])) {
    $where .= " AND mi.event_type_id = :event_type_id";
    $params[':event_type_id'] = (int)$_GET['event_type_id'];
}

// Query
$sql = "SELECT mi.id, u.monitor_id_code, mi.event_date, r.name AS region_name,
        mi.location, et.name AS event_type_name, se.name AS sub_event_name,
        a.name AS action_name, mi.source_url, mi.notes, mi.fatalities,
        mi.rating
        FROM monitored_information mi
        JOIN regions r ON mi.region_id = r.id
        JOIN event_types et ON mi.event_type_id = et.id
        JOIN sub_event_types se ON mi.sub_event_type_id = se.id
        JOIN actions a ON mi.action_id = a.id
        LEFT JOIN users u ON mi.user_id = u.id
        WHERE $where
        ORDER BY mi.event_date DESC, mi.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Load template if exists
$templatePath = __DIR__ . '/../img/Book1.xlsx';

if (file_exists($templatePath)) {
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();
    $startRow = 2;
} else {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $headers = [
        'Monitors_id','Event_date','Region','Location','Event_type',
        'Sub_event_type','Action','Source','Notes','Fatalities',
        'Rating','Created At'
    ];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $col++;
    }
    $startRow = 2;
}

// Ensure default row height is automatic so Excel can expand rows
$sheet->getDefaultRowDimension()->setRowHeight(-1);

// Ensure long-text columns wrap and columns auto-size so Excel can auto-fit row height on open
$longTextCols = ['H', 'I']; // H = Source, I = Notes
$endRow = $startRow + max(0, count($rows)) - 1;
if ($endRow < $startRow) {
    $endRow = $startRow;
}
foreach ($longTextCols as $c) {
    $sheet->getStyle($c . '1:' . $c . $endRow)->getAlignment()
        ->setWrapText(true)
        ->setVertical(Alignment::VERTICAL_TOP)
        ->setTextRotation(0);
}
// For long-text columns, use a fixed width and enable wrapping so rows expand vertically
$sheet->getColumnDimension('H')->setWidth(40);
$sheet->getColumnDimension('I')->setWidth(50);

// Force existing template rows to use automatic height (overrides fixed heights)
for ($r = 1; $r <= $endRow; $r++) {
    $sheet->getRowDimension($r)->setRowHeight(-1);
}

// Auto-size all output columns A..K so wrapped text can expand rows on open
foreach (range('A', 'K') as $c) {
    if (in_array($c, $longTextCols, true)) {
        // H and I have fixed widths to allow wrapping; skip autosize
        continue;
    }
    $sheet->getColumnDimension($c)->setAutoSize(true);
}
// Fill data
$rowNum = $startRow;
foreach ($rows as $r) {
    $sheet->setCellValueExplicit("A$rowNum", $r['monitor_id_code'], DataType::TYPE_STRING);
    $sheet->setCellValue("B$rowNum", $r['event_date']);
    $sheet->setCellValue("C$rowNum", $r['region_name']);
    $sheet->setCellValue("D$rowNum", $r['location']);
    $sheet->setCellValue("E$rowNum", $r['event_type_name']);
    $sheet->setCellValue("F$rowNum", $r['sub_event_name']);
    $sheet->setCellValue("G$rowNum", $r['action_name']);
    $sheet->setCellValue("H$rowNum", $r['source_url']);
    $sheet->setCellValue("I$rowNum", $r['notes']);
    $sheet->setCellValue("J$rowNum", (int)$r['fatalities']);
    $sheet->setCellValue("K$rowNum", (int)$r['rating']);
    // Let Excel adjust the row height automatically when opened
    $sheet->getRowDimension($rowNum)->setRowHeight(-1);
    $rowNum++;
}

// Output headers
$filename = 'monitored_export_' . date('Y-m-d_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

// Output file
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');

// Log
if (function_exists('logActivity')) {
    logActivity($pdo, $_SESSION['user_id'], 'export', 'monitored_information', null, [
        'format' => 'xlsx',
        'rows'   => count($rows)
    ]);
}

exit;
