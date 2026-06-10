<?php
require '../db.php';
require '../functions.php';
header('Content-Type: application/json');

$db       = getDB();
$batch    = trim($_GET['batch']          ?? '');
$stkBatch = trim($_GET['sticker_batch']  ?? '');
$margin   = max(0, (float)($_GET['margin'] ?? 3));
$gap      = max(0, (float)($_GET['gap']    ?? 3));

define('STK_W', 50.8);
define('STK_H', 22.098);

if ($batch === '' && $stkBatch === '') {
    echo json_encode(['pages'=>0,'slots_per_page'=>16,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[],'sticker_layout'=>[]]);
    exit;
}

$labels     = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels')->fetchAll();
$stickerLib = $db->query('SELECT id,name,img_path FROM stickers')->fetchAll();

$parsed = $batch !== '' ? parseBatchInput($batch, $labels) : [];
$slots  = [];
foreach ($parsed as $item)
    if (isset($item['label']))
        for ($i = 0; $i < $item['qty']; $i++) $slots[] = $item['label'];

$stickerMap = [];
foreach ($stickerLib as $s) $stickerMap[strtolower(trim($s['name']))] = $s;
$stickerSlots = [];
foreach (explode("\n", $stkBatch) as $line) {
    $line = trim($line);
    if (!$line) continue;
    if (preg_match('/^(.+?)\s+(\d+)$/', $line, $m)) {
        $key = strtolower(trim($m[1])); $qty = (int)$m[2];
        if (isset($stickerMap[$key]))
            for ($i = 0; $i < $qty; $i++) $stickerSlots[] = $stickerMap[$key];
    }
}

$totalLabels   = count($slots);
$totalStickers = count($stickerSlots);

if ($totalLabels === 0 && $totalStickers === 0) {
    echo json_encode(['pages'=>0,'slots_per_page'=>16,'total_labels'=>0,'filled'=>0,'empty'=>0,'layout'=>[],'sticker_layout'=>[],'errors'=>array_values(array_filter($parsed,fn($p)=>isset($p['error'])))]);
    exit;
}

// Label grid geometry
$lw = $totalLabels > 0 ? (float)$slots[0]['width_mm']  : 66.15;
$lh = $totalLabels > 0 ? (float)$slots[0]['height_mm'] : 40.75;

$usableW = 210 - 2*$margin;
$usableH = 297 - 2*$margin;
$totalW  = 2*$lw + $lh + 2*$gap;
$startX  = $margin + ($usableW - $totalW) / 2;
$nGridH  = 6*($lh+$gap) - $gap;
$nStartY = $margin + ($usableH - $nGridH) / 2;
$rGridH  = 4*($lw+$gap) - $gap;
$rStartY = $margin + ($usableH - $rGridH) / 2;
$rStartX = $startX + 2*($lw+$gap);

$slotsPerPage = 16;
$labelPages   = $totalLabels > 0 ? (int)ceil($totalLabels / $slotsPerPage) : 0;
$totalPages   = max($labelPages, 1);

// All 16 label slot positions (same every page)
$allSlotPos = [];
for ($s = 0; $s < $slotsPerPage; $s++) {
    if ($s < 12) {
        $col=$s%2; $row=(int)floor($s/2);
        $x=$startX+$col*($lw+$gap); $y=$nStartY+$row*($lh+$gap); $w=$lw; $h=$lh;
    } else {
        $row=$s-12; $x=$rStartX; $y=$rStartY+$row*($lw+$gap); $w=$lh; $h=$lw;
    }
    $allSlotPos[$s] = [$x,$y,$w,$h];
}

// Label layout entries
$layout = [];
for ($p = 0; $p < $labelPages; $p++) {
    for ($s = 0; $s < $slotsPerPage; $s++) {
        $idx = $p*$slotsPerPage + $s;
        [$x,$y,$w,$h] = $allSlotPos[$s];
        $rot = $s >= 12;
        $entry = ['page'=>$p+1,'slot'=>$s,'x_mm'=>round($x,4),'y_mm'=>round($y,4),'w_mm'=>$w,'h_mm'=>$h,'rotated'=>$rot,'label_name'=>null,'label_code'=>null,'img_path'=>null];
        if ($idx < $totalLabels) {
            $entry['label_name'] = $slots[$idx]['name'];
            $entry['label_code'] = $slots[$idx]['code'];
            $entry['img_path']   = $slots[$idx]['svg_path'];
        }
        $layout[] = $entry;
    }
}

// Sticker layout: fill free cells on label pages first, then overflow to extra pages
$stickerLayout = [];
$stickerIdx    = 0;

for ($p = 0; $p < $labelPages; $p++) {
    // Filled slots on this page
    $filledRects = [];
    for ($s = 0; $s < $slotsPerPage; $s++) {
        if ($p*$slotsPerPage + $s < $totalLabels)
            $filledRects[] = $allSlotPos[$s];
    }
    for ($ty = $margin; $ty + STK_H <= 297-$margin+0.001; $ty += STK_H) {
        for ($tx = $margin; $tx + STK_W <= 210-$margin+0.001; $tx += STK_W) {
            $blocked = false;
            foreach ($filledRects as [$lx,$ly,$lw2,$lh2]) {
                if ($tx < $lx+$lw2 && $tx+STK_W > $lx && $ty < $ly+$lh2 && $ty+STK_H > $ly) {
                    $blocked = true; break;
                }
            }
            if (!$blocked) {
                $entry = ['page'=>$p+1,'x_mm'=>round($tx,4),'y_mm'=>round($ty,4),'w_mm'=>STK_W,'h_mm'=>STK_H,'sticker_name'=>null,'img_path'=>null];
                if ($stickerIdx < $totalStickers) {
                    $entry['sticker_name'] = $stickerSlots[$stickerIdx]['name'];
                    $entry['img_path']     = $stickerSlots[$stickerIdx]['img_path'];
                    $stickerIdx++;
                }
                $stickerLayout[] = $entry;
            }
        }
    }
}

// Overflow sticker pages (all stickers, no labels)
$stkCols   = max(1, (int)floor($usableW / STK_W));
$stkRows   = max(1, (int)floor($usableH / STK_H));
$stkStartX = $margin + ($usableW - $stkCols*STK_W) / 2;
$stkStartY = $margin + ($usableH - $stkRows*STK_H) / 2;
while ($stickerIdx < $totalStickers) {
    $totalPages++;
    $pageNum = $totalPages;
    for ($row = 0; $row < $stkRows && $stickerIdx < $totalStickers; $row++) {
        for ($col = 0; $col < $stkCols && $stickerIdx < $totalStickers; $col++) {
            $stickerLayout[] = [
                'page'         => $pageNum,
                'x_mm'         => round($stkStartX + $col*STK_W, 4),
                'y_mm'         => round($stkStartY + $row*STK_H, 4),
                'w_mm'         => STK_W, 'h_mm' => STK_H,
                'sticker_name' => $stickerSlots[$stickerIdx]['name'],
                'img_path'     => $stickerSlots[$stickerIdx]['img_path'],
            ];
            $stickerIdx++;
        }
    }
}

echo json_encode([
    'pages'          => $totalPages,
    'slots_per_page' => $slotsPerPage,
    'label_w_mm'     => $lw,
    'label_h_mm'     => $lh,
    'total_labels'   => $totalLabels,
    'filled'         => $totalLabels,
    'empty'          => max(0, $labelPages*$slotsPerPage - $totalLabels),
    'layout'         => $layout,
    'sticker_layout' => $stickerLayout,
    'errors'         => array_values(array_filter($parsed, fn($p)=>isset($p['error']))),
]);
