<?php
require 'db.php';
require 'layout.php';
$db       = getDB();
$labels   = $db->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels ORDER BY name')->fetchAll();
$stickers = $db->query('SELECT id,name,img_path FROM stickers ORDER BY name')->fetchAll();
pageHeader('Generate PDF', 'generate');
?>
<style>
  .gen-layout {
    display: flex;
    gap: 0;
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
  }

  .sidebar {
    width: 220px;
    border-right: 1px solid #e5e5e5;
    overflow-y: auto;
    flex-shrink: 0;
    background: #fff
  }

  .sidebar-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 3px;
    transition: all 0.15s
  }

  .sidebar-item:hover {
    background: #f9f9f9
  }

  .sidebar-item.active {
    background: #f0f0f0;
    border-left: 3px solid #000;
    padding-left: 13px
  }

  .sidebar-item .si-name {
    font-weight: 500;
    font-size: 13px;
    color: #1a1a1a
  }

  .sidebar-item .si-dims {
    font-size: 11px;
    color: #999
  }

  .si-preview {
    padding: 16px;
    border-bottom: 1px solid #e5e5e5;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9
  }

  .si-preview img {
    max-width: 188px;
    max-height: 88px;
    object-fit: contain
  }

  .si-preview .no-sel {
    font-size: 12px;
    color: #bbb
  }

  .center {
    flex: 1;
    overflow: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    background: #fafafa
  }

  #a4Canvas {
    border: 1px solid #d9d9d9;
    background: #fff;
    display: block;
    width: 420px;
    height: 595px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08)
  }

  .canvas-meta {
    font-size: 12px;
    color: #666;
    text-align: center
  }

  .right-panel {
    width: 300px;
    border-left: 1px solid #e5e5e5;
    overflow-y: auto;
    flex-shrink: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fff
  }

  .panel-section {
    display: flex;
    flex-direction: column;
    gap: 10px
  }

  .panel-title {
    font-size: 10px;
    font-weight: 700;
    color: #666;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding-bottom: 8px;
    border-bottom: 1px solid #e5e5e5
  }

  .batch-rows {
    display: flex;
    flex-direction: column;
    gap: 6px
  }

  .batch-row {
    display: flex;
    align-items: center;
    gap: 6px
  }

  .batch-row select {
    flex: 1;
    min-width: 0;
    font-size: 12px;
    padding: 7px 10px;
    border: 1px solid #d9d9d9;
    background: #fff;
    color: #1a1a1a;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s
  }

  .batch-row select:hover {
    border-color: #999
  }

  .batch-row select:focus {
    border-color: #000;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08)
  }

  .batch-row .qty-wrap {
    display: flex;
    align-items: center;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    flex-shrink: 0;
    background: #fff
  }

  .batch-row .qty-wrap input {
    width: 40px;
    text-align: center;
    border: none;
    outline: none;
    font-size: 12px;
    padding: 6px 2px;
    -moz-appearance: textfield;
    background: transparent
  }

  .batch-row .qty-wrap input::-webkit-outer-spin-button,
  .batch-row .qty-wrap input::-webkit-inner-spin-button {
    -webkit-appearance: none
  }

  .batch-row .qty-wrap button {
    border: none;
    background: none;
    cursor: pointer;
    padding: 0 8px;
    font-size: 13px;
    color: #666;
    line-height: 30px;
    transition: all 0.2s
  }

  .batch-row .qty-wrap button:hover {
    color: #000;
    background: rgba(0, 0, 0, 0.04)
  }

  .batch-row .rm {
    border: none;
    background: none;
    cursor: pointer;
    color: #ccc;
    font-size: 13px;
    padding: 0 4px;
    flex-shrink: 0;
    transition: color 0.2s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center
  }

  .batch-row .rm:hover {
    color: #000
  }

  .add-row-btn {
    font-size: 12px;
    color: #666;
    background: #fff;
    border: 1px dashed #d9d9d9;
    padding: 8px;
    width: 100%;
    cursor: pointer;
    text-align: center;
    border-radius: 4px;
    transition: all 0.2s;
    font-weight: 500
  }

  .add-row-btn:hover {
    background: #f9f9f9;
    border-color: #999;
    color: #1a1a1a
  }

  .stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px
  }

  .stat-box {
    border: 1px solid #e5e5e5;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 4px
  }

  .stat-box .sv {
    font-size: 20px;
    font-weight: 700;
    color: #000
  }

  .stat-box .sl {
    font-size: 10px;
    color: #999;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px
  }

  .batch-errors {
    font-size: 11px;
    color: #d32f2f;
    background: #fff;
    border: 1px solid #e5e5e5;
    padding: 10px;
    display: none;
    border-radius: 4px;
    line-height: 1.6
  }

  .settings-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px
  }

  .settings-row>div {
    display: flex;
    flex-direction: column;
    gap: 6px
  }

  .page-nav {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    background: #fff;
    padding: 12px 20px;
    border-radius: 6px;
    border: 1px solid #e5e5e5
  }

  .page-nav button {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 4px
  }

  #slotEditor {
    display: none
  }

  .slot-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px
  }

  .slot-item {
    border: 1px solid #d9d9d9;
    padding: 8px;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 32px;
    cursor: grab;
    user-select: none;
    transition: all 0.15s;
    background: #fff;
    border-radius: 4px
  }

  .slot-item:hover {
    background: #f9f9f9;
    border-color: #999
  }

  .slot-item.empty {
    border-style: dashed;
    color: #bbb;
    background: #f5f5f5
  }

  .slot-item.rotated-slot {
    border-left: 3px solid #000
  }

  .slot-item.dragging {
    opacity: 0.5;
    cursor: grabbing
  }

  .slot-item.drag-over {
    outline: 2px dashed #666;
    background: #f0f0f0
  }

  .slot-item .sn {
    font-size: 10px;
    color: #999;
    flex-shrink: 0;
    min-width: 16px;
    font-weight: 600
  }

  .slot-item .grip {
    font-size: 11px;
    color: #d9d9d9;
    flex-shrink: 0;
    transition: color 0.2s
  }

  .slot-item:hover .grip {
    color: #999
  }

  .slot-item .sl-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    color: #1a1a1a
  }

  .slot-item .clr {
    font-size: 10px;
    color: #999;
    cursor: pointer;
    padding: 0 4px;
    flex-shrink: 0;
    margin-left: auto;
    transition: color 0.2s;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center
  }

  .slot-item .clr:hover {
    color: #000
  }

  .slot-legend {
    font-size: 11px;
    color: #999;
    line-height: 1.6
  }
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
      <div class="panel-title">Stickers</div>
      <div style="font-size:11px;color:#999;font-weight:500">2 in × 0.87 in, no gap</div>
      <?php if (empty($stickers)): ?>
        <div style="font-size:11px;color:#999;margin-top:6px">No stickers. <a href="stickers.php">Add some</a></div>
      <?php endif ?>
      <div class="batch-rows" id="stickerRows"></div>
      <?php if (!empty($stickers)): ?>
        <button class="add-row-btn" onclick="addStickerRow()">+ Add Sticker</button>
        <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;padding-top:4px">
          <input type="checkbox" id="autoFillStickers" onchange="onAutoFillChange()">
          <span>Fill free slots automatically</span>
        </label>
        <div id="autoFillInfo" style="font-size:11px;color:#999;display:none;margin-top:4px"></div>
      <?php endif ?>
    </div>

    <div class="panel-section" id="slotEditor">
      <div class="panel-title" style="display:flex;justify-content:space-between;align-items:center;gap:8px">
        <span>Slot Order — Page <span id="sePageNum">1</span></span>
        <button style="font-size:10px;padding:4px 10px;border-radius:3px;background:#f9f9f9" onclick="resetAssignments()">Reset</button>
      </div>
      <div class="slot-legend">Drag to reorder • Click to clear • <span style="border-left:2px solid #000;padding-left:3px">■</span> = rotated</div>
      <div class="slot-grid" id="slotGrid"></div>
    </div>

    <div class="panel-section">
      <div class="panel-title">Output Settings</div>
      <div class="settings-row">
        <div>
          <label class="field-label">Color Mode</label>
          <select id="colorMode">
            <option value="cmyk">CMYK</option>
            <option value="rgb">RGB</option>
          </select>
        </div>
        <div>
          <label class="field-label">Bleed (mm)</label>
          <input type="number" id="bleed" value="0" min="0" max="10" step="0.5">
        </div>
      </div>
      <div class="settings-row">
        <div>
          <label class="field-label">DPI</label>
          <select id="dpi">
            <option value="300">300</option>
            <option value="150">150</option>
          </select>
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
        <div class="stat-box">
          <div class="sv" id="stTotal">0</div>
          <div class="sl">Total Slots</div>
        </div>
        <div class="stat-box">
          <div class="sv" id="stFilled">0</div>
          <div class="sl">Filled</div>
        </div>
        <div class="stat-box">
          <div class="sv" id="stEmpty">0</div>
          <div class="sl">Empty</div>
        </div>
        <div class="stat-box">
          <div class="sv" id="stPages">0</div>
          <div class="sl">Pages</div>
        </div>
      </div>
    </div>

    <form id="pdfForm" method="POST" action="generate_pdf.php">
      <input type="hidden" name="batch" id="fBatch">
      <input type="hidden" name="sticker_batch" id="fStickerBatch">
      <input type="hidden" name="color_mode" id="fColorMode">
      <input type="hidden" name="bleed" id="fBleed">
      <input type="hidden" name="dpi" id="fDpi">
      <input type="hidden" name="margin" id="fMargin">
      <input type="hidden" name="gap" id="fGap">
      <input type="hidden" name="slot_map" id="fSlotMap">
      <button type="button" class="primary" style="width:100%;padding:12px;font-weight:600;border-radius:4px;font-size:13px" onclick="submitPDF()">Export PDF</button>
    </form>
  </div>
</div>

<script>
  const canvas = document.getElementById('a4Canvas');
  const ctx = canvas.getContext('2d');
  const MM2PX = canvas.width / 210;
  let currentPage = 1,
    totalPages = 1,
    layoutData = null;
  const imgCache = {};
  let pageAssignments = {};
  let dragSrcIdx = null;

  const labelOptions = <?= json_encode(array_map(fn($l) => ['name' => $l['name'], 'svg' => $l['svg_path']], $labels)) ?>;
  const stickerOptions = <?= json_encode(array_map(fn($s) => ['name' => $s['name'], 'img' => $s['img_path']], $stickers)) ?>;

  function makeBatchRow(options, containerId, placeholder) {
    const row = document.createElement('div');
    row.className = 'batch-row';
    const sel = document.createElement('select');
    const def = document.createElement('option');
    def.value = '';
    def.textContent = placeholder;
    sel.appendChild(def);
    options.forEach(o => {
      const opt = document.createElement('option');
      opt.value = o.name;
      opt.textContent = o.name;
      sel.appendChild(opt);
    });
    sel.addEventListener('change', schedulePreview);
    const qw = document.createElement('div');
    qw.className = 'qty-wrap';
    const inp = document.createElement('input');
    inp.type = 'number';
    inp.min = 0;
    inp.max = 999;
    inp.value = 1;
    inp.addEventListener('input', schedulePreview);
    const bm = document.createElement('button');
    bm.type = 'button';
    bm.textContent = '−';
    bm.onclick = () => {
      inp.value = Math.max(0, parseInt(inp.value || 0) - 1);
      schedulePreview();
    };
    const bp = document.createElement('button');
    bp.type = 'button';
    bp.textContent = '+';
    bp.onclick = () => {
      inp.value = parseInt(inp.value || 0) + 1;
      schedulePreview();
    };
    qw.appendChild(bm);
    qw.appendChild(inp);
    qw.appendChild(bp);
    const rm = document.createElement('button');
    rm.type = 'button';
    rm.className = 'rm';
    rm.textContent = '\u2715';
    rm.onclick = () => {
      row.remove();
      schedulePreview();
    };
    row.appendChild(sel);
    row.appendChild(qw);
    row.appendChild(rm);
    document.getElementById(containerId).appendChild(row);
  }

  function addBatchRow() {
    makeBatchRow(labelOptions, 'batchRows', 'Select label…');
  }

  function addStickerRow() {
    makeBatchRow(stickerOptions, 'stickerRows', 'Select sticker…');
  }

  function getBatchString(containerId) {
    const lines = [];
    document.querySelectorAll('#' + containerId + ' .batch-row').forEach(row => {
      const name = row.querySelector('select').value;
      const qty = parseInt(row.querySelector('input').value) || 0;
      if (name && qty > 0) lines.push(name + ' ' + qty);
    });
    return lines.join('\n');
  }

  addBatchRow();

  function loadImg(path) {
    if (imgCache[path] !== undefined) return Promise.resolve(imgCache[path]);
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => {
        imgCache[path] = img;
        resolve(img);
      };
      img.onerror = () => {
        imgCache[path] = null;
        resolve(null);
      };
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

  function schedulePreview() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchPreview, 350);
  }
  document.getElementById('margin').addEventListener('input', schedulePreview);
  document.getElementById('gap').addEventListener('input', schedulePreview);

  async function fetchPreview() {
    const batch = getBatchString('batchRows');
    const stkBatch = getEffectiveStickerBatch();
    const margin = document.getElementById('margin').value;
    const gap = document.getElementById('gap').value;
    if (!batch && !stkBatch) {
      resetCanvas();
      return;
    }

    const res = await fetch('api/preview.php?batch=' + encodeURIComponent(batch) +
      '&sticker_batch=' + encodeURIComponent(stkBatch) + '&margin=' + margin + '&gap=' + gap);
    const data = await res.json();

    layoutData = data;
    totalPages = data.pages || 1;
    currentPage = Math.min(currentPage, totalPages);

    // If auto-fill is on, update qty inputs to match free cells per page then re-fetch
    if (document.getElementById('autoFillStickers')?.checked) {
      const totalFree = Object.values(data.free_cells_per_page || {}).reduce((a, b) => a + b, 0);
      updateAutoFillQty(totalFree);
      updateAutoFillInfo(data);
      // Re-fetch only if qty actually changed (prevents infinite loop)
      const newBatch = getEffectiveStickerBatch();
      if (newBatch !== stkBatch) {
        const res2 = await fetch('api/preview.php?batch=' + encodeURIComponent(batch) +
          '&sticker_batch=' + encodeURIComponent(newBatch) + '&margin=' + margin + '&gap=' + gap);
        const data2 = await res2.json();
        layoutData = data2;
        totalPages = data2.pages || 1;
        currentPage = Math.min(currentPage, totalPages);
        updateStats(data2);
        showErrors(data2.errors || []);
        buildPageAssignments(data2);
        renderSlotEditor(currentPage);
        drawPage(currentPage);
        updatePageNav();
        return;
      }
    }

    updateStats(data);
    showErrors(data.errors || []);
    buildPageAssignments(data);
    renderSlotEditor(currentPage);
    drawPage(currentPage);
    updatePageNav();
  }

  function buildPageAssignments(data) {
    pageAssignments = {};
    for (let p = 1; p <= (data.pages || 1); p++) {
      const pg = data.layout.filter(s => s.page === p).sort((a, b) => a.slot - b.slot);
      pageAssignments[p] = pg.map(s => s.label_name ? {
          label_name: s.label_name,
          label_code: s.label_code,
          img_path: s.img_path
        } :
        null);
    }
  }

  function getEffectiveStickerBatch() {
    if (document.getElementById('autoFillStickers')?.checked) {
      // Use current qty from auto-fill inputs
      return getBatchString('stickerRows');
    }
    return getBatchString('stickerRows');
  }

  function updateAutoFillQty(totalFree) {
    // Distribute totalFree across sticker rows proportionally (or just set first row)
    const rows = document.querySelectorAll('#stickerRows .batch-row');
    if (!rows.length) return;
    // If single sticker type: set its qty to totalFree
    if (rows.length === 1) {
      rows[0].querySelector('input').value = totalFree;
      return;
    }
    // Multiple types: distribute evenly, remainder to last
    const each = Math.floor(totalFree / rows.length);
    let rem = totalFree - each * rows.length;
    rows.forEach((row, i) => {
      row.querySelector('input').value = each + (i === rows.length - 1 ? rem : 0);
    });
  }

  function updateAutoFillInfo(data) {
    const el = document.getElementById('autoFillInfo');
    if (!el) return;
    const free = Object.values(data.free_cells_per_page || {}).reduce((a, b) => a + b, 0);
    el.style.display = 'block';
    el.textContent = free + ' free slot' + (free !== 1 ? 's' : '') + ' across ' + (data.pages || 0) + ' page' + ((data.pages || 0) !== 1 ? 's' : '');
  }

  function onAutoFillChange() {
    const on = document.getElementById('autoFillStickers').checked;
    const btn = document.querySelector('#stickerRows')?.nextElementSibling;
    document.getElementById('autoFillInfo').style.display = on ? 'block' : 'none';
    schedulePreview();
  }

  function renderSlotEditor(pageNum) {
    if (!layoutData) return;
    document.getElementById('slotEditor').style.display = 'block';
    document.getElementById('sePageNum').textContent = pageNum;
    const grid = document.getElementById('slotGrid');
    grid.innerHTML = '';
    const assigns = pageAssignments[pageNum] || [];
    const metas = (layoutData.layout || []).filter(s => s.page === pageNum).sort((a, b) => a.slot - b.slot);
    assigns.forEach((assign, i) => {
      const meta = metas[i] || {};
      const isEmpty = !assign;
      const div = document.createElement('div');
      div.className = 'slot-item' + (isEmpty ? ' empty' : '') + (meta.rotated ? ' rotated-slot' : '');
      div.draggable = true;
      const numEl = document.createElement('span');
      numEl.className = 'sn';
      numEl.textContent = i + 1;
      const grip = document.createElement('span');
      grip.className = 'grip';
      grip.textContent = '\u2261';
      const nameEl = document.createElement('span');
      nameEl.className = 'sl-name';
      nameEl.textContent = isEmpty ? '(empty)' : assign.label_name;
      const btn = document.createElement('span');
      btn.className = 'clr';
      btn.textContent = isEmpty ? '↺' : '×';
      btn.title = isEmpty ? 'Restore' : 'Clear';
      btn.onclick = e => {
        e.stopPropagation();
        if (isEmpty) {
          const orig = layoutData.layout.find(s => s.page === pageNum && s.slot === i);
          pageAssignments[pageNum][i] = orig && orig.label_name ? {
            label_name: orig.label_name,
            label_code: orig.label_code,
            img_path: orig.img_path
          } : null;
        } else {
          pageAssignments[pageNum][i] = null;
        }
        renderSlotEditor(pageNum);
        drawPage(pageNum);
        recalcStats();
      };
      div.appendChild(numEl);
      div.appendChild(grip);
      div.appendChild(nameEl);
      div.appendChild(btn);
      div.addEventListener('dragstart', e => {
        dragSrcIdx = i;
        div.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
      });
      div.addEventListener('dragend', () => {
        div.classList.remove('dragging');
        dragSrcIdx = null;
      });
      div.addEventListener('dragover', e => {
        e.preventDefault();
        div.classList.add('drag-over');
      });
      div.addEventListener('dragleave', () => div.classList.remove('drag-over'));
      div.addEventListener('drop', e => {
        e.preventDefault();
        div.classList.remove('drag-over');
        if (dragSrcIdx === null || dragSrcIdx === i) return;
        const tmp = pageAssignments[pageNum][dragSrcIdx];
        pageAssignments[pageNum][dragSrcIdx] = pageAssignments[pageNum][i];
        pageAssignments[pageNum][i] = tmp;
        renderSlotEditor(pageNum);
        drawPage(pageNum);
        recalcStats();
      });
      grid.appendChild(div);
    });
  }

  function resetAssignments() {
    if (!layoutData) return;
    buildPageAssignments(layoutData);
    renderSlotEditor(currentPage);
    drawPage(currentPage);
    recalcStats();
  }

  function recalcStats() {
    let filled = 0,
      empty = 0;
    Object.values(pageAssignments).forEach(pg => pg.forEach(a => a ? filled++ : empty++));
    const total = Object.values(pageAssignments).reduce((s, p) => s + p.length, 0);
    document.getElementById('stTotal').textContent = total;
    document.getElementById('stFilled').textContent = filled;
    document.getElementById('stEmpty').textContent = empty;
    document.getElementById('stPages').textContent = totalPages;
  }

  function updateStats(d) {
    document.getElementById('stTotal').textContent = (d.pages || 0) * (d.slots_per_page || 0);
    document.getElementById('stFilled').textContent = d.filled || 0;
    document.getElementById('stEmpty').textContent = d.empty || 0;
    document.getElementById('stPages').textContent = d.pages || 0;
  }

  function showErrors(errors) {
    const el = document.getElementById('batchErrors');
    if (!errors.length) {
      el.style.display = 'none';
      return;
    }
    el.style.display = 'block';
    el.innerHTML = errors.map(e => '&#9888; "' + (e.name || e.raw) + '": ' + e.error).join('<br>');
  }

  function resetCanvas() {
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    drawBorder();
    layoutData = null;
    totalPages = 1;
    pageAssignments = {};
    updateStats({
      pages: 0,
      slots_per_page: 0,
      filled: 0,
      empty: 0
    });
    document.getElementById('pageIndicator').textContent = 'Page 1 / 1';
    document.getElementById('canvasMeta').textContent = 'A4 — 210 × 297 mm';
    document.getElementById('slotEditor').style.display = 'none';
  }

  function drawBorder() {
    ctx.strokeStyle = '#d0d0d0';
    ctx.lineWidth = 1;
    ctx.setLineDash([]);
    ctx.strokeRect(0.5, 0.5, canvas.width - 1, canvas.height - 1);
  }

  async function drawPage(pageNum) {
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    drawBorder();
    if (!layoutData) return;

    const STK_W = 50.8,
      STK_H = 22.098;
    const margin = parseFloat(document.getElementById('margin').value) || 3;

    const assigns = pageAssignments[pageNum] || [];
    const metas = (layoutData.layout || []).filter(s => s.page === pageNum).sort((a, b) => a.slot - b.slot);
    const labelSlots = metas.map((m, i) => ({
      ...m,
      ...(assigns[i] || {
        label_name: null,
        img_path: null
      })
    }));

    // Recompute free sticker cells from current assignments (not static sticker_layout)
    // Filled label rects for this page
    const filledRects = labelSlots
      .filter(s => s.img_path)
      .map(s => ({
        x: s.x_mm,
        y: s.y_mm,
        w: s.w_mm,
        h: s.h_mm
      }));

    // All sticker cells ordered the same way as the server
    const freeCells = [];
    for (let ty = margin; ty + STK_H <= 297 - margin + 0.001; ty += STK_H) {
      for (let tx = margin; tx + STK_W <= 210 - margin + 0.001; tx += STK_W) {
        const blocked = filledRects.some(r => tx < r.x + r.w && tx + STK_W > r.x && ty < r.y + r.h && ty + STK_H > r.y);
        if (!blocked) freeCells.push({
          x_mm: tx,
          y_mm: ty
        });
      }
    }

    // Sticker images for this page come from sticker_layout (ordered by page)
    // Count how many sticker cells were consumed by earlier pages
    let stkOffset = 0;
    for (let p = 1; p < pageNum; p++) {
      stkOffset += (layoutData.sticker_layout || []).filter(s => s.page === p && s.img_path).length;
    }
    const allStickerImgs = (layoutData.sticker_layout || []).filter(s => s.img_path).map(s => s.img_path);

    // Build sticker slots for this page by pairing free cells with sticker images
    const stkSlots = freeCells.map((cell, i) => ({
      ...cell,
      w_mm: STK_W,
      h_mm: STK_H,
      img_path: allStickerImgs[stkOffset + i] || null
    })).filter(s => s.img_path);

    // Preload all images
    const allPaths = [
      ...labelSlots.filter(s => s.img_path).map(s => s.img_path),
      ...stkSlots.map(s => s.img_path),
    ];
    await Promise.all([...new Set(allPaths)].map(p => loadImg(p)));

    // Draw stickers first
    stkSlots.forEach(slot => {
      const x = slot.x_mm * MM2PX,
        y = slot.y_mm * MM2PX;
      const w = slot.w_mm * MM2PX,
        h = slot.h_mm * MM2PX;
      const img = imgCache[slot.img_path];
      if (img) ctx.drawImage(img, x, y, w, h);
    });

    // Draw labels on top
    labelSlots.forEach(slot => {
      const x = slot.x_mm * MM2PX,
        y = slot.y_mm * MM2PX;
      const w = slot.w_mm * MM2PX,
        h = slot.h_mm * MM2PX;
      const img = slot.img_path ? imgCache[slot.img_path] : null;
      if (img) {
        if (slot.rotated) {
          ctx.save();
          ctx.translate(x + w / 2, y + h / 2);
          ctx.rotate(Math.PI / 2);
          ctx.drawImage(img, -h / 2, -w / 2, h, w);
          ctx.restore();
        } else {
          ctx.drawImage(img, x, y, w, h);
        }
      } else {
        ctx.strokeStyle = '#e0e0e0';
        ctx.lineWidth = 0.5;
        ctx.setLineDash([4, 3]);
        ctx.strokeRect(x + 0.5, y + 0.5, w - 1, h - 1);
        ctx.setLineDash([]);
        ctx.fillStyle = '#ddd';
        ctx.font = '10px system-ui,sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('#' + (slot.slot + 1), x + w / 2, y + h / 2);
      }
    });

    document.getElementById('canvasMeta').textContent =
      'A4 — 210 × 297 mm | 12 normal + 4 rotated = 16 label slots';
  }

  function changePage(delta) {
    currentPage = Math.min(Math.max(1, currentPage + delta), totalPages);
    updatePageNav();
    if (layoutData) {
      renderSlotEditor(currentPage);
      drawPage(currentPage);
    }
  }

  function updatePageNav() {
    document.getElementById('pageIndicator').textContent = 'Page ' + currentPage + ' / ' + totalPages;
    document.getElementById('prevPage').disabled = currentPage <= 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
  }

  function submitPDF() {
    const batch = getBatchString('batchRows');
    const stkBatch = getBatchString('stickerRows');
    if (!batch && !stkBatch) {
      alert('Please add at least one label or sticker.');
      return;
    }
    const slotMap = {};
    Object.entries(pageAssignments).forEach(([pageNum, assigns]) => {
      assigns.forEach((a, slotIdx) => {
        const pidx = parseInt(pageNum) - 1;
        const key = pidx + '_' + slotIdx;
        const orig = (layoutData.layout || []).find(s => s.page === parseInt(pageNum) && s.slot === slotIdx);
        const origPath = orig ? orig.img_path : null;
        const newPath = a ? a.img_path : null;
        if (newPath !== origPath) slotMap[key] = a ? {
          img_path: a.img_path
        } : null;
      });
    });

    document.getElementById('fBatch').value = batch;
    document.getElementById('fStickerBatch').value = stkBatch;
    document.getElementById('fColorMode').value = document.getElementById('colorMode').value;
    document.getElementById('fBleed').value = document.getElementById('bleed').value;
    document.getElementById('fDpi').value = document.getElementById('dpi').value;
    document.getElementById('fMargin').value = document.getElementById('margin').value;
    document.getElementById('fGap').value = document.getElementById('gap').value;
    document.getElementById('fSlotMap').value = JSON.stringify(slotMap);
    document.getElementById('pdfForm').submit();
  }

  resetCanvas();
</script>
<?php pageFooter(); ?>