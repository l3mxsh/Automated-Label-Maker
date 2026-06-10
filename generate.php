<?php
require 'db.php';
require 'layout.php';
$db       = getDB();
$labels   = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels ORDER BY name')->fetchAll();
$stickers = $db->query('SELECT id,name,img_path FROM stickers ORDER BY name')->fetchAll();
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
.right-panel{width:280px;border-left:0.5px solid #d0d0d0;overflow-y:auto;flex-shrink:0;padding:16px;display:flex;flex-direction:column;gap:16px}
.panel-section{display:flex;flex-direction:column;gap:8px}
.panel-title{font-size:11px;font-weight:600;color:#999;letter-spacing:.06em;text-transform:uppercase;padding-bottom:6px;border-bottom:0.5px solid #e0e0e0}
.batch-rows{display:flex;flex-direction:column;gap:4px}
.batch-row{display:flex;align-items:center;gap:4px}
.batch-row select{flex:1;min-width:0;font-size:12px;padding:4px 6px;border:0.5px solid #d0d0d0;background:#fff;color:#000;cursor:pointer}
.batch-row select:focus{border-color:#999;outline:none}
.batch-row .qty-wrap{display:flex;align-items:center;border:0.5px solid #d0d0d0;flex-shrink:0}
.batch-row .qty-wrap input{width:36px;text-align:center;border:none;outline:none;font-size:12px;padding:4px 2px;-moz-appearance:textfield}
.batch-row .qty-wrap input::-webkit-outer-spin-button,.batch-row .qty-wrap input::-webkit-inner-spin-button{-webkit-appearance:none}
.batch-row .qty-wrap button{border:none;background:none;cursor:pointer;padding:0 6px;font-size:14px;color:#666;line-height:28px}
.batch-row .qty-wrap button:hover{background:#f0f0f0;color:#000}
.batch-row .rm{border:none;background:none;cursor:pointer;color:#ccc;font-size:14px;padding:0 2px;flex-shrink:0}
.batch-row .rm:hover{color:#000}
.add-row-btn{font-size:11px;color:#666;background:none;border:0.5px dashed #d0d0d0;padding:5px;width:100%;cursor:pointer;text-align:center}
.add-row-btn:hover{background:#f8f8f8;color:#000}
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.stat-box{border:0.5px solid #e0e0e0;padding:8px 10px}
.stat-box .sv{font-size:18px;font-weight:600}
.stat-box .sl{font-size:11px;color:#999;margin-top:2px}
.batch-errors{font-size:11px;color:#000;background:#f5f5f5;border:0.5px solid #d0d0d0;padding:8px;display:none}
.settings-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.settings-row>div{display:flex;flex-direction:column;gap:4px}
.page-nav{display:flex;align-items:center;gap:8px;font-size:12px}
.page-nav button{padding:3px 9px;font-size:12px}
#slotEditor{display:none}
.slot-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px}
.slot-item{border:0.5px solid #d0d0d0;padding:5px 7px;font-size:11px;display:flex;align-items:center;gap:4px;min-height:28px;cursor:grab;user-select:none;transition:background .1s}
.slot-item:hover{background:#f5f5f5}
.slot-item.empty{border-style:dashed;color:#bbb}
.slot-item.rotated-slot{border-left:2px solid #000}
.slot-item.dragging{opacity:.3;cursor:grabbing}
.slot-item.drag-over{outline:1.5px dashed #666;background:#f0f0f0}
.slot-item .sn{font-size:10px;color:#bbb;flex-shrink:0;min-width:14px}
.slot-item .grip{font-size:11px;color:#d0d0d0;flex-shrink:0}
.slot-item .sl-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}
.slot-item .clr{font-size:10px;color:#bbb;cursor:pointer;padding:0 2px;flex-shrink:0;margin-left:auto}
.slot-item .clr:hover{color:#000}
.slot-legend{font-size:11px;color:#999;line-height:1.6}
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

  <!-- CENTER -->
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
      <div class="panel-title">Labels</div>
      <div class="batch-rows" id="batchRows"></div>
      <button class="add-row-btn" onclick="addBatchRow()">+ Add Label</button>
      <div class="batch-errors" id="batchErrors"></div>
    </div>

    <div class="panel-section">
      <div class="panel-title">Stickers <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:#bbb">2in &times; 0.87in, no gap</span></div>
      <?php if (empty($stickers)): ?>
      <div style="font-size:11px;color:#bbb">No stickers. <a href="stickers.php">Add some &rarr;</a></div>
      <?php endif ?>
      <div class="batch-rows" id="stickerRows"></div>
      <?php if (!empty($stickers)): ?>
      <button class="add-row-btn" onclick="addStickerRow()">+ Add Sticker</button>
      <?php endif ?>
    </div>

    <div class="panel-section" id="slotEditor">
      <div class="panel-title" style="display:flex;justify-content:space-between;align-items:center">
        <span>Slot Order &mdash; Page <span id="sePageNum">1</span></span>
        <button style="font-size:10px;padding:2px 7px" onclick="resetAssignments()">Reset</button>
      </div>
      <div class="slot-legend">Drag to reorder &bull; &#x2715; to empty &bull; <span style="border-left:2px solid #000;padding-left:3px">&#9632;</span> = rotated</div>
      <div class="slot-grid" id="slotGrid"></div>
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
          <label class="field-label">Gap (mm)</label>
          <input type="number" id="gap" value="3" min="0" max="10" step="0.5">
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
      <input type="hidden" name="batch"         id="fBatch">
      <input type="hidden" name="sticker_batch" id="fStickerBatch">
      <input type="hidden" name="color_mode"    id="fColorMode">
      <input type="hidden" name="bleed"         id="fBleed">
      <input type="hidden" name="dpi"           id="fDpi">
      <input type="hidden" name="margin"        id="fMargin">
      <input type="hidden" name="gap"           id="fGap">
      <input type="hidden" name="slot_map"      id="fSlotMap">
      <button type="button" class="primary" style="width:100%;padding:10px" onclick="submitPDF()">Export PDF</button>
    </form>
  </div>
</div>

<script>
const canvas = document.getElementById('a4Canvas');
const ctx    = canvas.getContext('2d');
const MM2PX  = canvas.width / 210;
let currentPage = 1, totalPages = 1, layoutData = null;
const imgCache = {};
let pageAssignments = {};
let dragSrcIdx = null;

const labelOptions   = <?= json_encode(array_map(fn($l) => ['name'=>$l['name'],'svg'=>$l['svg_path']], $labels)) ?>;
const stickerOptions = <?= json_encode(array_map(fn($s) => ['name'=>$s['name'],'img'=>$s['img_path']], $stickers)) ?>;

function makeBatchRow(options, containerId, placeholder) {
  const row = document.createElement('div');
  row.className = 'batch-row';
  const sel = document.createElement('select');
  const def = document.createElement('option');
  def.value = ''; def.textContent = placeholder;
  sel.appendChild(def);
  options.forEach(o => {
    const opt = document.createElement('option');
    opt.value = o.name; opt.textContent = o.name;
    sel.appendChild(opt);
  });
  sel.addEventListener('change', schedulePreview);
  const qw = document.createElement('div'); qw.className = 'qty-wrap';
  const inp = document.createElement('input'); inp.type='number'; inp.min=0; inp.max=999; inp.value=1;
  inp.addEventListener('input', schedulePreview);
  const bm = document.createElement('button'); bm.type='button'; bm.textContent='−';
  bm.onclick = () => { inp.value = Math.max(0, parseInt(inp.value||0)-1); schedulePreview(); };
  const bp = document.createElement('button'); bp.type='button'; bp.textContent='+';
  bp.onclick = () => { inp.value = parseInt(inp.value||0)+1; schedulePreview(); };
  qw.appendChild(bm); qw.appendChild(inp); qw.appendChild(bp);
  const rm = document.createElement('button'); rm.type='button'; rm.className='rm'; rm.textContent='\u2715';
  rm.onclick = () => { row.remove(); schedulePreview(); };
  row.appendChild(sel); row.appendChild(qw); row.appendChild(rm);
  document.getElementById(containerId).appendChild(row);
}

function addBatchRow()   { makeBatchRow(labelOptions,   'batchRows',   'Select label…');   }
function addStickerRow() { makeBatchRow(stickerOptions, 'stickerRows', 'Select sticker…'); }

function getBatchString(containerId) {
  const lines = [];
  document.querySelectorAll('#' + containerId + ' .batch-row').forEach(row => {
    const name = row.querySelector('select').value;
    const qty  = parseInt(row.querySelector('input').value) || 0;
    if (name && qty > 0) lines.push(name + ' ' + qty);
  });
  return lines.join('\n');
}

addBatchRow();

function loadImg(path) {
  if (imgCache[path] !== undefined) return Promise.resolve(imgCache[path]);
  return new Promise(resolve => {
    const img = new Image();
    img.onload  = () => { imgCache[path] = img; resolve(img); };
    img.onerror = () => { imgCache[path] = null; resolve(null); };
    img.src = path;
  });
}

document.querySelectorAll('.sidebar-item').forEach(el => {
  el.addEventListener('click', () => {
    document.querySelectorAll('.sidebar-item').forEach(x => x.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('siPreview').innerHTML = '<img src="' + el.dataset.svg + '" alt="' + el.dataset.name + '">';
  });
});

let debounceTimer;
function schedulePreview() { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchPreview, 350); }
document.getElementById('margin').addEventListener('input', schedulePreview);
document.getElementById('gap').addEventListener('input', schedulePreview);

async function fetchPreview() {
  const batch       = getBatchString('batchRows');
  const stkBatch    = getBatchString('stickerRows');
  const margin      = document.getElementById('margin').value;
  const gap         = document.getElementById('gap').value;
  if (!batch && !stkBatch) { resetCanvas(); return; }

  const res  = await fetch('api/preview.php?batch=' + encodeURIComponent(batch) +
    '&sticker_batch=' + encodeURIComponent(stkBatch) + '&margin=' + margin + '&gap=' + gap);
  const data = await res.json();

  layoutData  = data;
  totalPages  = data.pages || 1;
  currentPage = Math.min(currentPage, totalPages);

  pageAssignments = {};
  for (let p = 1; p <= totalPages; p++) {
    const pg = data.layout.filter(s => s.page === p).sort((a,b) => a.slot - b.slot);
    pageAssignments[p] = pg.map(s => s.label_name
      ? { label_name: s.label_name, label_code: s.label_code, img_path: s.img_path }
      : null);
  }

  updateStats(data);
  showErrors(data.errors || []);
  renderSlotEditor(currentPage);
  drawPage(currentPage);
  updatePageNav();
}

function renderSlotEditor(pageNum) {
  if (!layoutData) return;
  document.getElementById('slotEditor').style.display = 'block';
  document.getElementById('sePageNum').textContent = pageNum;
  const grid  = document.getElementById('slotGrid');
  grid.innerHTML = '';
  const assigns = pageAssignments[pageNum] || [];
  const metas   = (layoutData.layout || []).filter(s => s.page === pageNum).sort((a,b) => a.slot - b.slot);
  assigns.forEach((assign, i) => {
    const meta = metas[i] || {};
    const isEmpty = !assign;
    const div = document.createElement('div');
    div.className = 'slot-item' + (isEmpty ? ' empty' : '') + (meta.rotated ? ' rotated-slot' : '');
    div.draggable = true;
    const numEl = document.createElement('span'); numEl.className='sn'; numEl.textContent=i+1;
    const grip  = document.createElement('span'); grip.className='grip'; grip.textContent='\u2261';
    const nameEl = document.createElement('span'); nameEl.className='sl-name'; nameEl.textContent=isEmpty?'(empty)':assign.label_name;
    const btn = document.createElement('span'); btn.className='clr';
    btn.textContent = isEmpty ? '\u21ba' : '\u2715';
    btn.title       = isEmpty ? 'Restore' : 'Clear';
    btn.onclick = e => {
      e.stopPropagation();
      if (isEmpty) {
        const orig = layoutData.layout.find(s => s.page === pageNum && s.slot === i);
        pageAssignments[pageNum][i] = orig && orig.label_name ? { label_name: orig.label_name, label_code: orig.label_code, img_path: orig.img_path } : null;
      } else { pageAssignments[pageNum][i] = null; }
      renderSlotEditor(pageNum); drawPage(pageNum); recalcStats();
    };
    div.appendChild(numEl); div.appendChild(grip); div.appendChild(nameEl); div.appendChild(btn);
    div.addEventListener('dragstart', e => { dragSrcIdx=i; div.classList.add('dragging'); e.dataTransfer.effectAllowed='move'; });
    div.addEventListener('dragend',   () => { div.classList.remove('dragging'); dragSrcIdx=null; });
    div.addEventListener('dragover',  e => { e.preventDefault(); div.classList.add('drag-over'); });
    div.addEventListener('dragleave', () => div.classList.remove('drag-over'));
    div.addEventListener('drop', e => {
      e.preventDefault(); div.classList.remove('drag-over');
      if (dragSrcIdx===null || dragSrcIdx===i) return;
      const tmp = pageAssignments[pageNum][dragSrcIdx];
      pageAssignments[pageNum][dragSrcIdx] = pageAssignments[pageNum][i];
      pageAssignments[pageNum][i] = tmp;
      renderSlotEditor(pageNum); drawPage(pageNum); recalcStats();
    });
    grid.appendChild(div);
  });
}

function resetAssignments() {
  if (!layoutData) return;
  pageAssignments = {};
  for (let p = 1; p <= totalPages; p++) {
    const pg = layoutData.layout.filter(s => s.page === p).sort((a,b) => a.slot - b.slot);
    pageAssignments[p] = pg.map(s => s.label_name ? { label_name: s.label_name, label_code: s.label_code, img_path: s.img_path } : null);
  }
  renderSlotEditor(currentPage); drawPage(currentPage); recalcStats();
}

function recalcStats() {
  let filled = 0, empty = 0;
  Object.values(pageAssignments).forEach(pg => pg.forEach(a => a ? filled++ : empty++));
  const total = Object.values(pageAssignments).reduce((s,p) => s+p.length, 0);
  document.getElementById('stTotal').textContent  = total;
  document.getElementById('stFilled').textContent = filled;
  document.getElementById('stEmpty').textContent  = empty;
  document.getElementById('stPages').textContent  = totalPages;
}

function updateStats(d) {
  document.getElementById('stTotal').textContent  = (d.pages||0) * (d.slots_per_page||0);
  document.getElementById('stFilled').textContent = d.filled||0;
  document.getElementById('stEmpty').textContent  = d.empty||0;
  document.getElementById('stPages').textContent  = d.pages||0;
}

function showErrors(errors) {
  const el = document.getElementById('batchErrors');
  if (!errors.length) { el.style.display='none'; return; }
  el.style.display='block';
  el.innerHTML = errors.map(e => '&#9888; "' + (e.name||e.raw) + '": ' + e.error).join('<br>');
}

function resetCanvas() {
  ctx.fillStyle='#fff'; ctx.fillRect(0,0,canvas.width,canvas.height); drawBorder();
  layoutData=null; totalPages=1; pageAssignments={};
  updateStats({pages:0,slots_per_page:0,filled:0,empty:0});
  document.getElementById('pageIndicator').textContent='Page 1 / 1';
  document.getElementById('canvasMeta').textContent='A4 \u2014 210 \u00d7 297 mm';
  document.getElementById('slotEditor').style.display='none';
}

function drawBorder() {
  ctx.strokeStyle='#d0d0d0'; ctx.lineWidth=1; ctx.setLineDash([]);
  ctx.strokeRect(0.5,0.5,canvas.width-1,canvas.height-1);
}

async function drawPage(pageNum) {
  ctx.fillStyle='#fff'; ctx.fillRect(0,0,canvas.width,canvas.height); drawBorder();
  if (!layoutData) return;

  // Collect all image paths to preload
  const assigns = pageAssignments[pageNum] || [];
  const metas   = (layoutData.layout||[]).filter(s=>s.page===pageNum).sort((a,b)=>a.slot-b.slot);
  const labelSlots = metas.map((m,i) => ({...m,...(assigns[i]||{label_name:null,img_path:null})}));

  const stkSlots = (layoutData.sticker_layout||[]).filter(s=>s.page===pageNum);

  const allPaths = [
    ...labelSlots.filter(s=>s.img_path).map(s=>s.img_path),
    ...stkSlots.filter(s=>s.img_path).map(s=>s.img_path),
  ];
  await Promise.all([...new Set(allPaths)].map(p=>loadImg(p)));

  // Draw stickers first (behind labels)
  stkSlots.forEach(slot => {
    const x = slot.x_mm * MM2PX, y = slot.y_mm * MM2PX;
    const w = slot.w_mm * MM2PX, h = slot.h_mm * MM2PX;
    const img = slot.img_path ? imgCache[slot.img_path] : null;
    if (img) {
      ctx.drawImage(img, x, y, w, h);
    } else {
      ctx.strokeStyle='#f0e0ff'; ctx.lineWidth=0.5; ctx.setLineDash([3,2]);
      ctx.strokeRect(x+0.5,y+0.5,w-1,h-1); ctx.setLineDash([]);
      ctx.fillStyle='#e8d8f8'; ctx.font='8px system-ui,sans-serif';
      ctx.textAlign='center'; ctx.textBaseline='middle';
      ctx.fillText('sticker', x+w/2, y+h/2);
    }
  });

  // Draw labels on top
  labelSlots.forEach(slot => {
    const x = slot.x_mm * MM2PX, y = slot.y_mm * MM2PX;
    const w = slot.w_mm * MM2PX, h = slot.h_mm * MM2PX;
    const img = slot.img_path ? imgCache[slot.img_path] : null;
    if (img) {
      if (slot.rotated) {
        ctx.save(); ctx.translate(x+w/2,y+h/2); ctx.rotate(Math.PI/2);
        ctx.drawImage(img,-h/2,-w/2,h,w); ctx.restore();
      } else { ctx.drawImage(img,x,y,w,h); }
    } else {
      ctx.strokeStyle='#e0e0e0'; ctx.lineWidth=0.5; ctx.setLineDash([4,3]);
      ctx.strokeRect(x+0.5,y+0.5,w-1,h-1); ctx.setLineDash([]);
      ctx.fillStyle='#ddd'; ctx.font='10px system-ui,sans-serif';
      ctx.textAlign='center'; ctx.textBaseline='middle';
      ctx.fillText('#'+(slot.slot+1), x+w/2, y+h/2);
    }
  });

  document.getElementById('canvasMeta').textContent =
    'A4 \u2014 210 \u00d7 297 mm  |  12 normal + 4 rotated = 16 label slots';
}

function changePage(delta) {
  currentPage = Math.min(Math.max(1, currentPage+delta), totalPages);
  updatePageNav();
  if (layoutData) { renderSlotEditor(currentPage); drawPage(currentPage); }
}

function updatePageNav() {
  document.getElementById('pageIndicator').textContent = 'Page '+currentPage+' / '+totalPages;
  document.getElementById('prevPage').disabled = currentPage<=1;
  document.getElementById('nextPage').disabled = currentPage>=totalPages;
}

function submitPDF() {
  const batch    = getBatchString('batchRows');
  const stkBatch = getBatchString('stickerRows');
  if (!batch && !stkBatch) { alert('Please add at least one label or sticker.'); return; }

  const slotMap = {};
  Object.entries(pageAssignments).forEach(([pageNum, assigns]) => {
    assigns.forEach((a, slotIdx) => {
      const pidx = parseInt(pageNum) - 1;
      const key  = pidx + '_' + slotIdx;
      const orig = (layoutData.layout||[]).find(s=>s.page===parseInt(pageNum)&&s.slot===slotIdx);
      const origPath = orig ? orig.img_path : null;
      const newPath  = a ? a.img_path : null;
      if (newPath !== origPath) slotMap[key] = a ? { img_path: a.img_path } : null;
    });
  });

  document.getElementById('fBatch')        .value = batch;
  document.getElementById('fStickerBatch') .value = stkBatch;
  document.getElementById('fColorMode')    .value = document.getElementById('colorMode').value;
  document.getElementById('fBleed')        .value = document.getElementById('bleed').value;
  document.getElementById('fDpi')          .value = document.getElementById('dpi').value;
  document.getElementById('fMargin')       .value = document.getElementById('margin').value;
  document.getElementById('fGap')          .value = document.getElementById('gap').value;
  document.getElementById('fSlotMap')      .value = JSON.stringify(slotMap);
  document.getElementById('pdfForm').submit();
}

resetCanvas();
</script>
<?php pageFooter(); ?>
