<?php
ob_start();
require 'vendor/autoload.php';
require 'db.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: generate.php'); exit; }

$batchText = trim($_POST['batch']          ?? '');
$stkBatch  = trim($_POST['sticker_batch']  ?? '');
$bleed     = max(0, (float)($_POST['bleed']  ?? 0));
$margin    = max(0, (float)($_POST['margin'] ?? 3));
$gap       = max(0, (float)($_POST['gap']    ?? 3));
$dpi       = in_array((int)($_POST['dpi'] ?? 300), [150,300]) ? (int)$_POST['dpi'] : 300;
$slotMap   = json_decode($_POST['slot_map'] ?? '{}', true) ?: [];

define('STK_W', 50.8);
define('STK_H', 22.098);

$db     = getDB();
$labels = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels')->fetchAll();
$parsed = parseBatchInput($batchText, $labels);

$slots = [];
foreach ($parsed as $item)
    if (isset($item['label']))
        for ($i = 0; $i < $item['qty']; $i++) $slots[] = $item['label'];

$stickerLib = $db->query('SELECT id,name,img_path FROM stickers')->fetchAll();
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

if (empty($slots) && empty($stickerSlots)) { ob_end_clean(); die('No valid labels or stickers.'); }

$lw = !empty($slots) ? (float)$slots[0]['width_mm']  : 66.15;
$lh = !empty($slots) ? (float)$slots[0]['height_mm'] : 40.75;

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
$labelPages   = !empty($slots) ? (int)ceil(count($slots) / $slotsPerPage) : 0;

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

// Overflow sticker page geometry (full page, no labels)
$stkCols   = max(1, (int)floor($usableW / STK_W));
$stkRows   = max(1, (int)floor($usableH / STK_H));
$stkStartX = $margin + ($usableW - $stkCols*STK_W) / 2;
$stkStartY = $margin + ($usableH - $stkRows*STK_H) / 2;

// GD rotate 90° CW
$rotCache = [];
function getRotatedPath(string $path, string $ext): string {
    global $rotCache;
    if (isset($rotCache[$path])) return $rotCache[$path];
    $src = ($ext==='png') ? imagecreatefrompng($path) : imagecreatefromjpeg($path);
    imagealphablending($src,false); imagesavealpha($src,true);
    $rot = imagerotate($src,-90,0);
    imagealphablending($rot,false); imagesavealpha($rot,true);
    $tmp = tempnam(sys_get_temp_dir(),'lbl_').'.'.$ext;
    ($ext==='png') ? imagepng($rot,$tmp,0) : imagejpeg($rot,$tmp,100);
    imagedestroy($src); imagedestroy($rot);
    return $rotCache[$path] = $tmp;
}

ob_end_clean();

$pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->SetCreator('OVXI Label Positioner');
$pdf->SetTitle('Labels');
$pdf->SetAutoPageBreak(false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0,0,0);

$stickerIdx = 0;

// ── Label pages: stickers fill free cells, labels drawn on top ──
for ($pageIdx = 0; $pageIdx < $labelPages; $pageIdx++) {
    $pdf->AddPage();

    // Which slots are filled on this page?
    $filledRects = [];
    for ($s = 0; $s < $slotsPerPage; $s++) {
        $slotIdx     = $pageIdx * $slotsPerPage + $s;
        $overrideKey = $pageIdx . '_' . $s;
        $isFilled = array_key_exists($overrideKey, $slotMap)
            ? ($slotMap[$overrideKey] !== null)
            : ($slotIdx < count($slots));
        if ($isFilled) $filledRects[] = $allSlotPos[$s];
    }

    // Draw stickers in free cells (not overlapping any filled label slot)
    for ($ty = $margin; $ty + STK_H <= 297-$margin+0.001; $ty += STK_H) {
        for ($tx = $margin; $tx + STK_W <= 210-$margin+0.001; $tx += STK_W) {
            if ($stickerIdx >= count($stickerSlots)) break 2;
            $blocked = false;
            foreach ($filledRects as [$lx,$ly,$lw2,$lh2]) {
                if ($tx < $lx+$lw2 && $tx+STK_W > $lx && $ty < $ly+$lh2 && $ty+STK_H > $ly) {
                    $blocked = true; break;
                }
            }
            if ($blocked) continue;
            $stk  = $stickerSlots[$stickerIdx++];
            $path = $stk['img_path'];
            if (!$path || !file_exists($path)) continue;
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $type = ($ext==='png') ? 'PNG' : 'JPEG';
            $pdf->Image($path, $tx, $ty, STK_W, STK_H, $type,'','N',false,0,'',false,false,0);
        }
    }

    // Draw labels on top
    for ($s = 0; $s < $slotsPerPage; $s++) {
        $slotIdx     = $pageIdx * $slotsPerPage + $s;
        $overrideKey = $pageIdx . '_' . $s;
        $path = null;
        if (array_key_exists($overrideKey, $slotMap)) {
            if ($slotMap[$overrideKey] === null) continue;
            $path = $slotMap[$overrideKey]['img_path'] ?? null;
        } elseif ($slotIdx < count($slots)) {
            $path = $slots[$slotIdx]['svg_path'];
        } else { continue; }

        if (!$path || !file_exists($path)) continue;
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = ($ext==='png') ? 'PNG' : 'JPEG';
        [$x,$y,$w,$h] = $allSlotPos[$s];

        if ($s < 12) {
            $pdf->Image($path, $x-$bleed, $y-$bleed, $w+2*$bleed, $h+2*$bleed, $type,'','N',false,0,'',false,false,0);
        } else {
            $rp = getRotatedPath($path, $ext);
            $pdf->Image($rp, $x-$bleed, $y-$bleed, $w+2*$bleed, $h+2*$bleed, $type,'','N',false,0,'',false,false,0);
        }
    }
}

// ── Overflow sticker pages ──
while ($stickerIdx < count($stickerSlots)) {
    $pdf->AddPage();
    for ($row = 0; $row < $stkRows && $stickerIdx < count($stickerSlots); $row++) {
        for ($col = 0; $col < $stkCols && $stickerIdx < count($stickerSlots); $col++) {
            $stk  = $stickerSlots[$stickerIdx++];
            $path = $stk['img_path'];
            if (!$path || !file_exists($path)) continue;
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $type = ($ext==='png') ? 'PNG' : 'JPEG';
            $tx   = $stkStartX + $col * STK_W;
            $ty   = $stkStartY + $row * STK_H;
            $pdf->Image($path, $tx, $ty, STK_W, STK_H, $type,'','N',false,0,'',false,false,0);
        }
    }
}

foreach ($rotCache as $tmp) @unlink($tmp);
$pdf->Output('ovxi_labels_'.date('Ymd_His').'.pdf', 'D');
