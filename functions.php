<?php

function calculateGrid(
    float $labelW, float $labelH,
    float $pageW = 210, float $pageH = 297,
    float $margin = 3, float $gap = 4.5,
    int $forceCols = 0, int $forceRows = 0
): array {
    $usableW = $pageW - 2 * $margin;
    $usableH = $pageH - 2 * $margin;
    $cols = $forceCols > 0 ? $forceCols : max(1, (int) floor(($usableW + $gap) / ($labelW + $gap)));
    $rows = $forceRows > 0 ? $forceRows : max(1, (int) floor(($usableH + $gap) / ($labelH + $gap)));
    $gridW = $cols * ($labelW + $gap) - $gap;
    $gridH = $rows * ($labelH + $gap) - $gap;
    $startX = $margin + ($usableW - $gridW) / 2;
    $startY = $margin + ($usableH - $gridH) / 2;
    return [
        'cols'   => $cols,
        'rows'   => $rows,
        'slots'  => $cols * $rows,
        'startX' => $startX,
        'startY' => $startY,
        'cellW'  => $labelW + $gap,
        'cellH'  => $labelH + $gap,
        'labelW' => $labelW,
        'labelH' => $labelH,
        'gap'    => $gap,
    ];
}

function parseImageDimensions(string $path): array {
    $info = @getimagesize($path);
    if (!$info) return ['width_mm' => 0, 'height_mm' => 0];

    $pxW = $info[0];
    $pxH = $info[1];

    // Read DPI from EXIF/PNG metadata
    $dpiX = 96; $dpiY = 96;
    $type = $info[2];

    if ($type === IMAGETYPE_JPEG) {
        $exif = @exif_read_data($path);
        if (!empty($exif['XResolution']) && !empty($exif['ResolutionUnit'])) {
            $res = evalFraction($exif['XResolution']);
            if ($exif['ResolutionUnit'] == 2 && $res > 1) { $dpiX = $res; $dpiY = evalFraction($exif['YResolution'] ?? $exif['XResolution']); }
            elseif ($exif['ResolutionUnit'] == 3 && $res > 1) { $dpiX = $res * 2.54; $dpiY = $dpiX; }
        }
    } elseif ($type === IMAGETYPE_PNG) {
        // PNG pHYs chunk: read raw bytes
        $raw = file_get_contents($path, false, null, 0, 40960);
        $pos = strpos($raw, 'pHYs');
        if ($pos !== false) {
            $ppuX = unpack('N', substr($raw, $pos + 4, 4))[1];
            $ppuY = unpack('N', substr($raw, $pos + 8, 4))[1];
            $unit = ord($raw[$pos + 12]);
            if ($unit === 1 && $ppuX > 1) {
        $dpiX = round($ppuX * 0.0254, 4);
                $dpiY = round($ppuY * 0.0254, 4);
            }
        }
    }

    if ($dpiX < 1) $dpiX = 96;
    if ($dpiY < 1) $dpiY = 96;

    return [
        'width_mm'  => round($pxW * 25.4 / $dpiX, 2),
        'height_mm' => round($pxH * 25.4 / $dpiY, 2),
    ];
}

function evalFraction(mixed $val): float {
    if (is_string($val) && strpos($val, '/') !== false) {
        [$n, $d] = explode('/', $val);
        return $d != 0 ? (float)$n / (float)$d : 0;
    }
    return (float)$val;
}

function parseBatchInput(string $text, array $labels): array {
    $labelMap = [];
    foreach ($labels as $l) {
        $labelMap[strtolower(trim($l['name']))] = $l;
    }

    $results = [];
    foreach (explode("\n", $text) as $i => $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Strip optional colon: "Eclipse: 7" → "Eclipse 7"
        $line = preg_replace('/:\s*(\d+)$/', ' $1', $line);

        // Split last token as quantity
        if (!preg_match('/^(.+?)\s+(\d+)$/', $line, $m)) {
            $results[] = ['raw' => $line, 'error' => 'Invalid format'];
            continue;
        }

        $name = trim($m[1]);
        $qty  = (int)$m[2];
        $key  = strtolower($name);

        if (!isset($labelMap[$key])) {
            $results[] = ['raw' => $line, 'name' => $name, 'qty' => $qty, 'error' => 'Label not found'];
        } else {
            $results[] = ['raw' => $line, 'name' => $name, 'qty' => $qty, 'label' => $labelMap[$key]];
        }
    }
    return $results;
}
