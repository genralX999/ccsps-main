<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$path = __DIR__ . '/../img/Book1.xlsx';
if (!file_exists($path)) {
    echo "Template not found: $path\n";
    exit(1);
}

echo "Loading template...\n";
$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();

// Backup original
$backup = $path . '.bak.' . date('Ymd_His');
copy($path, $backup);
echo "Backup created: $backup\n";

// Force default and per-row automatic heights
$sheet->getDefaultRowDimension()->setRowHeight(-1);
$highestRow = (int)$sheet->getHighestRow();
for ($r = 1; $r <= $highestRow; $r++) {
    $sheet->getRowDimension($r)->setRowHeight(-1);
}

// Ensure long-text columns wrap and align top
$cols = ['H', 'I'];
foreach ($cols as $c) {
    $sheet->getStyle($c . '1:' . $c . $highestRow)->getAlignment()
        ->setWrapText(true)
        ->setVertical(Alignment::VERTICAL_TOP)
        ->setTextRotation(0);
    // also mark auto-size for columns
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

// Auto-size A..K
foreach (range('A', 'K') as $c) {
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

// Save back to original path
echo "Saving fixed template...\n";
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($path);
echo "Saved: $path\n";
echo "Done.\n";
