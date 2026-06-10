<?php
require '../db.php';
require '../functions.php';
header('Content-Type: application/json');

$db     = getDB();
$batch  = trim($_GET['batch'] ?? '');
$margin = max(0, (float)($_GET['margin'] ?? 3));
$gap    = max(0, (float)($_GET['gap'] ?? 4.5));
$forceCols = max(0, (int)($_GET['cols'] ?? 0));
$forceRows = max(0, (int)($_GET['rows'] ?? 0));

if ($batch === '') { echo json_encode(['pages'=>0,'slots_per_page'=>0,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[]]); exit; }

$labels = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels')->fetchAll();
$parsed = parseBatchInput($batch, $labels);

// Build flat slot list
$slots = [];
foreach ($parsed as $item) {
    if (isset($item['label'])) {
        for ($i = 0; $i < $item['qty']; $i++) $slots[] = $item['label'];
    }
}

$totalLabels = count($slots);
if ($totalLabels === 0) { echo json_encode(['pages'=>0,'slots_per_page'=>0,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[],'errors'=>array_filter($parsed,fn($p)=>isset($p['error']))]); exit; }

// Use first valid label's dimensions for grid (or largest)
$refLabel = null;
foreach ($parsed as $item) {
    if (isset($item['label'])) { $refLabel = $item['label']; break; }
}

$grid        = calculateGrid((float)$refLabel['width_mm'], (float)$refLabel['height_mm'], 210, 297, $margin, $gap, $forceCols, $forceRows);
$slotsPerPage = $grid['slots'];
$pages       = (int)ceil($totalLabels / $slotsPerPage);
$totalSlots  = $pages * $slotsPerPage;
$empty       = $totalSlots - $totalLabels;

$layout = [];
for ($p = 0; $p < $pages; $p++) {
    for ($s = 0; $s < $slotsPerPage; $s++) {
        $idx = $p * $slotsPerPage + $s;
        $col = $s % $grid['cols'];
        $row = (int)floor($s / $grid['cols']);
        $entry = ['page'=>$p+1,'col'=>$col,'row'=>$row,'label_name'=>null,'label_code'=>null,'img_path'=>null];
        if ($idx < $totalLabels) {
            $entry['label_name'] = $slots[$idx]['name'];
            $entry['label_code'] = $slots[$idx]['code'];
            $entry['img_path']   = $slots[$idx]['svg_path'];
        }
        $layout[] = $entry;
    }
}

echo json_encode([
    'pages'         => $pages,
    'slots_per_page'=> $slotsPerPage,
    'cols'          => $grid['cols'],
    'rows'          => $grid['rows'],
    'startX'        => $grid['startX'],
    'startY'        => $grid['startY'],
    'cellW'         => $grid['cellW'],
    'cellH'         => $grid['cellH'],
    'gap_mm'        => $gap,
    'label_w_mm'    => (float)$refLabel['width_mm'],
    'label_h_mm'    => (float)$refLabel['height_mm'],
    'total_labels'  => $totalLabels,
    'filled'        => $totalLabels,
    'empty'         => $empty,
    'layout'        => $layout,
    'errors'        => array_values(array_filter($parsed, fn($p) => isset($p['error']))),
]);
