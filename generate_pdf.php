<?php
ob_start();
require 'vendor/autoload.php';
require 'db.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: generate.php'); exit; }

$batchText = trim($_POST['batch']  ?? '');
$bleed     = max(0, (float)($_POST['bleed']  ?? 0));
$margin    = max(0, (float)($_POST['margin'] ?? 3));
$gap       = max(0, (float)($_POST['gap']    ?? 3));
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

$ref = $slots[0];
$lw  = (float)$ref['width_mm'];
$lh  = (float)$ref['height_mm'];

$usableW = 210 - 2 * $margin;
$usableH = 297 - 2 * $margin;
$totalW  = 2 * $lw + $lh + 2 * $gap;
$startX  = $margin + ($usableW - $totalW) / 2;
$nGridH  = 6 * ($lh + $gap) - $gap;
$nStartY = $margin + ($usableH - $nGridH) / 2;
$rGridH  = 4 * ($lw + $gap) - $gap;
$rStartY = $margin + ($usableH - $rGridH) / 2;
$rStartX = $startX + 2 * ($lw + $gap);

$slotsPerPage = 16;
$totalPages   = (int)ceil(count($slots) / $slotsPerPage);

// GD rotate 90° CW and cache to temp file
$rotCache = [];
function getRotatedPath(string $path, string $ext): string {
    global $rotCache;
    if (isset($rotCache[$path])) return $rotCache[$path];
    $src     = ($ext === 'png') ? imagecreatefrompng($path) : imagecreatefromjpeg($path);
    $rotated = imagerotate($src, -90, 0); // negative = CW
    $tmp     = tempnam(sys_get_temp_dir(), 'lbl_') . '.' . $ext;
    if ($ext === 'png') {
        imagesavealpha($rotated, true);
        imagepng($rotated, $tmp, 0);
    } else {
        imagejpeg($rotated, $tmp, 95);
    }
    imagedestroy($src);
    imagedestroy($rotated);
    return $rotCache[$path] = $tmp;
}

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
        $path  = $label['svg_path'];
        if (!file_exists($path)) continue;
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = ($ext === 'png') ? 'PNG' : 'JPEG';

        if ($s < 12) {
            $col = $s % 2;
            $row = (int)floor($s / 2);
            $x   = $startX + $col * ($lw + $gap);
            $y   = $nStartY + $row * ($lh + $gap);
            $pdf->Image($path, $x - $bleed, $y - $bleed, $lw + 2*$bleed, $lh + 2*$bleed, $type, '', 'N', true, $dpi, '', false, false, 0);
        } else {
            $row     = $s - 12;
            $x       = $rStartX;
            $y       = $rStartY + $row * ($lw + $gap);
            $rotPath = getRotatedPath($path, $ext);
            // After 90° CW rotation: original lw becomes height, lh becomes width
            $pdf->Image($rotPath, $x - $bleed, $y - $bleed, $lh + 2*$bleed, $lw + 2*$bleed, $type, '', 'N', true, $dpi, '', false, false, 0);
        }
    }
}

// Clean up temp files
foreach ($rotCache as $tmp) @unlink($tmp);

$filename = 'ovxi_labels_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
