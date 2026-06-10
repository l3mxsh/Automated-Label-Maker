<?php
require '../db.php';
require '../functions.php';
header('Content-Type: application/json');

$db     = getDB();
$batch  = trim($_GET['batch'] ?? '');
$margin = max(0, (float)($_GET['margin'] ?? 3));
$gap    = max(0, (float)($_GET['gap']    ?? 3));

if ($batch === '') { echo json_encode(['pages'=>0,'slots_per_page'=>16,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[]]); exit; }

$labels = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels')->fetchAll();
$parsed = parseBatchInput($batch, $labels);

$slots = [];
foreach ($parsed as $item) {
    if (isset($item['label'])) {
        for ($i = 0; $i < $item['qty']; $i++) $slots[] = $item['label'];
    }
}

$totalLabels = count($slots);
if ($totalLabels === 0) {
    echo json_encode(['pages'=>0,'slots_per_page'=>16,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[],'errors'=>array_values(array_filter($parsed,fn($p)=>isset($p['error'])))]);
    exit;
}

$ref  = $slots[0];
$lw   = (float)$ref['width_mm'];
$lh   = (float)$ref['height_mm'];

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
$pages        = (int)ceil($totalLabels / $slotsPerPage);
$totalSlots   = $pages * $slotsPerPage;
$empty        = $totalSlots - $totalLabels;

$layout = [];
for ($p = 0; $p < $pages; $p++) {
    for ($s = 0; $s < $slotsPerPage; $s++) {
        $idx = $p * $slotsPerPage + $s;

        if ($s < 12) {
            $col  = $s % 2;
            $row  = (int)floor($s / 2);
            $x    = $startX + $col * ($lw + $gap);
            $y    = $nStartY + $row * ($lh + $gap);
            $w    = $lw; $h = $lh;
            $rotated = false;
        } else {
            $row  = $s - 12;
            $x    = $rStartX;
            $y    = $rStartY + $row * ($lw + $gap);
            $w    = $lh;   // 40.75mm
            $h    = $lw;   // 66.15mm
            $rotated = true;
        }

        $entry = [
            'page'       => $p + 1,
            'slot'       => $s,
            'x_mm'       => round($x, 4),
            'y_mm'       => round($y, 4),
            'w_mm'       => $w,
            'h_mm'       => $h,
            'rotated'    => $rotated,
            'label_name' => null,
            'label_code' => null,
            'img_path'   => null,
        ];
        if ($idx < $totalLabels) {
            $entry['label_name'] = $slots[$idx]['name'];
            $entry['label_code'] = $slots[$idx]['code'];
            $entry['img_path']   = $slots[$idx]['svg_path'];
        }
        $layout[] = $entry;
    }
}

echo json_encode([
    'pages'          => $pages,
    'slots_per_page' => $slotsPerPage,
    'label_w_mm'     => $lw,
    'label_h_mm'     => $lh,
    'total_labels'   => $totalLabels,
    'filled'         => $totalLabels,
    'empty'          => $empty,
    'layout'         => $layout,
    'errors'         => array_values(array_filter($parsed, fn($p) => isset($p['error']))),
]);
