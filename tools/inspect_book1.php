<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__ . '/../img/Book1.xlsx';
if (!file_exists($path)) {
    echo "Template not found: $path\n";
    exit(1);
}

$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = (int)$sheet->getHighestRow();
echo "Inspecting template: $path\n";
echo "Highest row: $highestRow\n\n";

echo "Row heights (null means not explicitly set / default):\n";
for ($r = 1; $r <= $highestRow; $r++) {
    $rd = $sheet->getRowDimension($r);
    $h = $rd->getRowHeight();
    $custom = ($h !== null);
    printf("Row %d: height=%s custom=%s\n", $r, var_export($h, true), $custom ? 'yes' : 'no');
}

echo "\nMerged cell ranges:\n";
$merged = $sheet->getMergeCells();
if (empty($merged)) {
    echo "(none)\n";
} else {
    foreach ($merged as $range) {
        echo "- $range\n";
    }
}

echo "\nColumn H/I alignment and rotation sample:\n";
for ($r = 1; $r <= min(30, $highestRow); $r++) {
    $aH = $sheet->getStyle('H' . $r)->getAlignment();
    $aI = $sheet->getStyle('I' . $r)->getAlignment();
    printf("Row %d: H(wrap=%s rot=%s vert=%s) | I(wrap=%s rot=%s vert=%s)\n",
        $r,
        var_export($aH->getWrapText(), true),
        var_export($aH->getTextRotation(), true),
        var_export($aH->getVertical(), true),
        var_export($aI->getWrapText(), true),
        var_export($aI->getTextRotation(), true),
        var_export($aI->getVertical(), true)
    );
}

echo "\nDone.\n";
