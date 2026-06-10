<?php
require 'db.php';
require 'layout.php';
$labels = getDB()->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels ORDER BY name')->fetchAll();
pageHeader('Generate PDF', 'generate');
?>
<style>
.gen-layout{display:flex;gap:0;height:calc(100vh - 44px - 48px);overflow:hidden}
.sidebar{width:200px;border-right:0.5px solid #d0d0d0;overflow-y:auto;flex-shrink:0}
.sidebar-item{padding:10px 14px;border-bottom:0.5px solid #e0e0e0;cursor:pointer;display:flex;flex-direction:column;gap:2px}
.sidebar-item:hover{background:#f8f8f8}
.sidebar-item.active{background:#f0f0f0}
.sidebar-item .si-name{font-weight:500;font-size:13px}
.sidebar-item .si-dims{font-size:11px;color:#999}
.si-preview{padding:12px 14px;border-bottom:0.5px solid #d0d0d0;min-height:80px;display:flex;align-items:center;justify-content:center;background:#fafafa}
.si-preview img{max-width:160px;max-height:72px;object-fit:contain}
.si-preview .no-sel{font-size:12px;color:#bbb}
.center{flex:1;overflow:auto;padding:20px;display:flex;flex-direction:column;align-items:center;gap:12px}
#a4Canvas{border:0.5px solid #d0d0d0;background:#fff;display:block;width:420px;height:595px}
.canvas-meta{font-size:12px;color:#666;text-align:center}
.right-panel{width:260px;border-left:0.5px solid #d0d0d0;overflow-y:auto;flex-shrink:0;padding:16px;display:flex;flex-direction:column;gap:16px}
.panel-section{display:flex;flex-direction:column;gap:8px}
.panel-title{font-size:11px;font-weight:600;color:#999;letter-spacing:.06em;text-transform:uppercase;padding-bottom:6px;border-bottom:0.5px solid #e0e0e0}
#batchInput{height:160px;resize:vertical;font-size:12px;font-family:monospace;line-height:1.6}
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.stat-box{border:0.5px solid #e0e0e0;padding:8px 10px}
.stat-box .sv{font-size:18px;font-weight:600}
.stat-box .sl{font-size:11px;color:#999;margin-top:2px}
.batch-errors{font-size:11px;color:#000;background:#f5f5f5;border:0.5px solid #d0d0d0;padding:8px;display:none}
.settings-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.settings-row>div{display:flex;flex-direction:column;gap:4px}
.page-nav{display:flex;align-items:center;gap:8px;font-size:12px}
.page-nav button{padding:3px 9px;font-size:12px}
</style>

<div class="gen-layout">

  <!-- LEFT SIDEBAR -->
  <div class="sidebar">
    <div class="si-preview" id="siPreview">
      <span class="no-sel">No label selected</span>
    </div>
    <?php foreach ($labels as $l): ?>
    <div class="sidebar-item"
         data-svg="<?= htmlspecialchars($l['svg_path']) ?>"
         data-name="<?= htmlspecialchars($l['name']) ?>">
      <span class="si-name"><?= htmlspecialchars($l['name']) ?></span>
      <span class="si-dims"><?= $l['width_mm'] ?> &times; <?= $l['height_mm'] ?> mm</span>
    </div>
    <?php endforeach ?>
    <?php if (empty($labels)): ?>
    <div style="padding:14px;font-size:12px;color:#bbb">No labels. <a href="labels.php">Add some &rarr;</a></div>
    <?php endif ?>
  </div>

  <!-- CENTER: A4 canvas -->
  <div class="center">
    <div class="page-nav">
      <button id="prevPage" onclick="changePage(-1)">&#8249;</button>
      <span id="pageIndicator">Page 1 / 1</span>
      <button id="nextPage" onclick="changePage(1)">&#8250;</button>
    </div>
    <canvas id="a4Canvas" width="840" height="1190"></canvas>
    <div class="canvas-meta" id="canvasMeta">A4 &mdash; 210 &times; 297 mm</div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <div class="panel-section">
      <div class="panel-title">Batch Input</div>
      <textarea id="batchInput" placeholder="Eclipse 7&#10;Solace 5&#10;Madagascar 3&#10;BDC: 5"></textarea>
      <div class="batch-errors" id="batchErrors"></div>
    </div>

    <div class="panel-section">
      <div class="panel-title">Output Settings</div>
      <div class="settings-row">
        <div>
          <label class="field-label">Color Mode</label>
          <select id="colorMode"><option value="cmyk">CMYK</option><option value="rgb">RGB</option></select>
        </div>
        <div>
          <label class="field-label">Bleed (mm)</label>
          <input type="number" id="bleed" value="0" min="0" max="10" step="0.5">
        </div>
      </div>
      <div class="settings-row">
        <div>
          <label class="field-label">DPI</label>
          <select id="dpi"><option value="300">300</option><option value="150">150</option></select>
        </div>
        <div>
          <label class="field-label">Margin (mm)</label>
          <input type="number" id="margin" value="3" min="0" max="20" step="1">
        </div>
      </div>
      <div class="settings-row">
        <div>
          <label class="field-label">Columns</label>
          <input type="number" id="cols" value="2" min="1" max="20" step="1">
        </div>
        <div>
          <label class="field-label">Rows</label>
          <input type="number" id="rows" value="6" min="1" max="30" step="1">
        </div>
      </div>
      <div class="settings-row">
        <div>
          <label class="field-label">Gap (mm)</label>
          <input type="number" id="gap" value="4.5" min="0" max="10" step="0.5">
        </div>
      </div>
    </div>

    <div class="panel-section">
      <div class="panel-title">Sheet Stats</div>
      <div class="stat-grid">
        <div class="stat-box"><div class="sv" id="stTotal">0</div><div class="sl">Total Slots</div></div>
        <div class="stat-box"><div class="sv" id="stFilled">0</div><div class="sl">Filled</div></div>
        <div class="stat-box"><div class="sv" id="stEmpty">0</div><div class="sl">Empty</div></div>
        <div class="stat-box"><div class="sv" id="stPages">0</div><div class="sl">Pages</div></div>
      </div>
    </div>

    <form id="pdfForm" method="POST" action="generate_pdf.php">
      <input type="hidden" name="batch"      id="fBatch">
      <input type="hidden" name="color_mode" id="fColorMode">
      <input type="hidden" name="bleed"      id="fBleed">
      <input type="hidden" name="dpi"        id="fDpi">
      <input type="hidden" name="margin"     id="fMargin">
      <input type="hidden" name="cols"       id="fCols">
      <input type="hidden" name="rows"       id="fRows">
      <input type="hidden" name="gap"        id="fGap">
      <button type="button" class="primary" style="width:100%;padding:10px" onclick="submitPDF()">Export PDF</button>
    </form>
  </div>
</div>

<script>
const canvas = document.getElementById('a4Canvas');
const ctx    = canvas.getContext('2d');
const MM2PX  = canvas.width / 210; // 840/210 = 4px per mm — matches A4 exactly
let currentPage = 1, totalPages = 1, layoutData = null;
const imgCache = {};

function loadImg(path) {
  if (imgCache[path]) return Promise.resolve(imgCache[path]);
  return new Promise(resolve => {
    const img = new Image();
    img.onload  = () => { imgCache[path] = img; resolve(img); };
    img.onerror = () => { imgCache[path] = null; resolve(null); };
    img.src = path;
  });
}

// Sidebar click: show SVG preview
document.querySelectorAll('.sidebar-item').forEach(el => {
  el.addEventListener('click', () => {
    document.querySelectorAll('.sidebar-item').forEach(x => x.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('siPreview').innerHTML =
      '<img src="' + el.dataset.svg + '" alt="' + el.dataset.name + '">';
  });
});

// Debounced preview trigger
let debounceTimer;
function schedulePreview() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPreview, 350);
}
document.getElementById('batchInput').addEventListener('input', schedulePreview);
document.getElementById('margin').addEventListener('input', schedulePreview);
document.getElementById('gap').addEventListener('input', schedulePreview);
document.getElementById('cols').addEventListener('input', schedulePreview);
document.getElementById('rows').addEventListener('input', schedulePreview);

async function fetchPreview() {
  const batch  = document.getElementById('batchInput').value.trim();
  const margin = document.getElementById('margin').value;
  const gap    = document.getElementById('gap').value;
  const cols   = document.getElementById('cols').value;
  const rows   = document.getElementById('rows').value;
  if (!batch) { resetCanvas(); return; }

  const res  = await fetch('api/preview.php?batch=' + encodeURIComponent(batch) + '&margin=' + margin + '&gap=' + gap + '&cols=' + cols + '&rows=' + rows);
  const data = await res.json();

  // Dimensions come directly from preview API response
  layoutData  = data;
  totalPages  = data.pages || 1;
  currentPage = Math.min(currentPage, totalPages);
  updateStats(data);
  showErrors(data.errors || []);
  drawPage(currentPage);
  updatePageNav();
}

function updateStats(d) {
  document.getElementById('stTotal') .textContent = (d.pages || 0) * (d.slots_per_page || 0);
  document.getElementById('stFilled').textContent = d.filled || 0;
  document.getElementById('stEmpty') .textContent = d.empty  || 0;
  document.getElementById('stPages') .textContent = d.pages  || 0;
}

function showErrors(errors) {
  const el = document.getElementById('batchErrors');
  if (!errors.length) { el.style.display = 'none'; return; }
  el.style.display = 'block';
  el.innerHTML = errors.map(e => '&#9888; "' + (e.name || e.raw) + '": ' + e.error).join('<br>');
}

// Canvas drawing
function resetCanvas() {
  ctx.fillStyle = '#fff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  drawBorder();
  layoutData = null; totalPages = 1;
  updateStats({pages:0, slots_per_page:0, filled:0, empty:0});
  document.getElementById('pageIndicator').textContent = 'Page 1 / 1';
  document.getElementById('canvasMeta').textContent = 'A4 \u2014 210 \u00d7 297 mm';
}

function drawBorder() {
  ctx.strokeStyle = '#d0d0d0';
  ctx.lineWidth   = 1;
  ctx.setLineDash([]);
  ctx.strokeRect(0.5, 0.5, canvas.width - 1, canvas.height - 1);
}

async function drawPage(pageNum) {
  ctx.fillStyle = '#fff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  drawBorder();
  if (!layoutData || !layoutData.layout || !layoutData.cols) return;

  const lw = layoutData.label_w_mm;
  const lh = layoutData.label_h_mm;
  const gap = layoutData.gap_mm;
  if (!lw || !lh) return;

  // Use startX/Y and cellW/H directly from API — identical to PHP
  const startX = layoutData.startX;
  const startY = layoutData.startY;
  const cellW  = layoutData.cellW;
  const cellH  = layoutData.cellH;

  const pageSlots = layoutData.layout.filter(s => s.page === pageNum);

  const uniquePaths = [...new Set(pageSlots.filter(s => s.img_path).map(s => s.img_path))];
  await Promise.all(uniquePaths.map(p => loadImg(p)));

  pageSlots.forEach(slot => {
    const x = (startX + slot.col * cellW) * MM2PX;
    const y = (startY + slot.row * cellH) * MM2PX;
    const w = lw * MM2PX;
    const h = lh * MM2PX;

    if (slot.img_path && imgCache[slot.img_path]) {
      ctx.drawImage(imgCache[slot.img_path], x, y, w, h);
    } else if (slot.label_name) {
      ctx.fillStyle = '#f0f0f0';
      ctx.fillRect(x, y, w, h);
      ctx.save();
      ctx.beginPath(); ctx.rect(x, y, w, h); ctx.clip();
      ctx.fillStyle    = '#000';
      ctx.font         = 'bold ' + Math.max(7, Math.min(11, w / 8)) + 'px system-ui,sans-serif';
      ctx.textAlign    = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(slot.label_name, x + w / 2, y + h / 2, w - 4);
      ctx.restore();
    } else {
      ctx.strokeStyle = '#ddd';
      ctx.lineWidth   = 0.5;
      ctx.setLineDash([3, 3]);
      ctx.strokeRect(x, y, w, h);
      ctx.setLineDash([]);
    }
  });

  document.getElementById('canvasMeta').textContent =
    'A4 — 210 × 297 mm  |  ' + layoutData.cols + ' × ' + layoutData.rows + ' grid';
}

function changePage(delta) {
  currentPage = Math.min(Math.max(1, currentPage + delta), totalPages);
  updatePageNav();
  if (layoutData) drawPage(currentPage);
}

function updatePageNav() {
  document.getElementById('pageIndicator').textContent = 'Page ' + currentPage + ' / ' + totalPages;
  document.getElementById('prevPage').disabled = currentPage <= 1;
  document.getElementById('nextPage').disabled = currentPage >= totalPages;
}

function submitPDF() {
  const batch = document.getElementById('batchInput').value.trim();
  if (!batch) { alert('Please enter batch input first.'); return; }
  document.getElementById('fBatch')    .value = batch;
  document.getElementById('fColorMode').value = document.getElementById('colorMode').value;
  document.getElementById('fBleed')    .value = document.getElementById('bleed').value;
  document.getElementById('fDpi')      .value = document.getElementById('dpi').value;
  document.getElementById('fMargin')   .value = document.getElementById('margin').value;
  document.getElementById('fCols')     .value = document.getElementById('cols').value;
  document.getElementById('fRows')     .value = document.getElementById('rows').value;
  document.getElementById('fGap')      .value = document.getElementById('gap').value;
  document.getElementById('pdfForm').submit();
}

resetCanvas();
</script>
<?php pageFooter(); ?>
