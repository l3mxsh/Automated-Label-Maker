<?php
ob_start();
require 'vendor/autoload.php';
require 'db.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: generate.php'); exit; }

$batchText = trim($_POST['batch']  ?? '');
$bleed     = max(0, (float)($_POST['bleed']  ?? 3));
$margin    = max(0, (float)($_POST['margin'] ?? 3));
$gap       = max(0, (float)($_POST['gap']    ?? 4.5));
$forceCols = max(0, (int)($_POST['cols']   ?? 0));
$forceRows = max(0, (int)($_POST['rows']   ?? 0));
$dpi       = in_array((int)($_POST['dpi'] ?? 300), [150, 300]) ? (int)$_POST['dpi'] : 300;

$db     = getDB();
$labels = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels')->fetchAll();
$parsed = parseBatchInput($batchText, $labels);

$slots = [];
foreach ($parsed as $item) {
    if (!isset($item['label'])) continue;
    for ($i = 0; $i < $item['qty']; $i++) $slots[] = $item['label'];
}

if (empty($slots)) { ob_end_clean(); die('No valid labels in batch input.'); }

$refLabel     = $slots[0];
$grid         = calculateGrid((float)$refLabel['width_mm'], (float)$refLabel['height_mm'], 210, 297, $margin, $gap, $forceCols, $forceRows);
$slotsPerPage = $grid['slots'];
$totalPages   = (int)ceil(count($slots) / $slotsPerPage);

ob_end_clean();

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('OVXI Label Positioner');
$pdf->SetTitle('Labels');
$pdf->SetAutoPageBreak(false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);

for ($pageIdx = 0; $pageIdx < $totalPages; $pageIdx++) {
    $pdf->AddPage();

    for ($s = 0; $s < $slotsPerPage; $s++) {
        $slotIdx = $pageIdx * $slotsPerPage + $s;
        if ($slotIdx >= count($slots)) break;

        $label = $slots[$slotIdx];
        $col   = $s % $grid['cols'];
        $row   = (int)floor($s / $grid['cols']);

        $x = $grid['startX'] + $col * $grid['cellW'];
        $y = $grid['startY'] + $row * $grid['cellH'];
        $w = $label['width_mm'];
        $h = $label['height_mm'];

        // Bleed expands render box inward from cell boundary, never overlapping gap
        $maxBleed = $grid['gap'] / 2;
        $b  = min($bleed, $maxBleed);
        $rx = $x - $b;
        $ry = $y - $b;
        $rw = $w + 2 * $b;
        $rh = $h + 2 * $b;

        $path = $label['svg_path']; // column still named svg_path in DB
        if (!file_exists($path)) continue;

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = ($ext === 'png') ? 'PNG' : 'JPEG';

        try {
            $pdf->Image($path, $rx, $ry, $rw, $rh, $type, '', 'N', true, $dpi, '', false, false, 0);
        } catch (Exception $e) {
            $pdf->SetDrawColor(180);
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($x, $y, $w, $h);
        }
    }
}

$filename = 'ovxi_labels_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
