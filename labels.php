<?php
require 'db.php';
require 'functions.php';
require 'layout.php';

$db  = getDB();
$msg = '';

// Delete
if (isset($_GET['delete'])) {
    $row = $db->prepare('SELECT svg_path FROM labels WHERE id=?');
    $row->execute([(int)$_GET['delete']]);
    $r = $row->fetch();
    if ($r && file_exists($r['svg_path'])) unlink($r['svg_path']);
    $db->prepare('DELETE FROM labels WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: labels.php?deleted=1'); exit;
}

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $file = $_FILES['img'] ?? null;

    if ($name && $code && $file && $file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $dest = 'uploads/svgs/' . uniqid('lbl_', true) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dest);
            $dims = parseImageDimensions($dest);
            if ($dims['width_mm'] > 0 && $dims['height_mm'] > 0) {
                $db->prepare('INSERT INTO labels (name,code,svg_path,width_mm,height_mm) VALUES (?,?,?,?,?)')
                   ->execute([$name, $code, $dest, $dims['width_mm'], $dims['height_mm']]);
                header('Location: labels.php?added=1'); exit;
            } else {
                unlink($dest);
                $msg = '<div class="msg err">Could not read image dimensions. Ensure the file is a valid PNG/JPEG exported at 300 DPI.</div>';
            }
        } else {
            $msg = '<div class="msg err">Only PNG or JPEG files are accepted.</div>';
        }
    } else {
        $msg = '<div class="msg err">All fields are required.</div>';
    }
}

$labels = $db->query('SELECT * FROM labels ORDER BY created_at DESC')->fetchAll();
pageHeader('Label Library', 'labels');
?>
<?php if (isset($_GET['added'])): ?><div class="msg ok">Label added successfully.</div><?php endif ?>
<?php if (isset($_GET['deleted'])): ?><div class="msg ok">Label deleted.</div><?php endif ?>
<?= $msg ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1 style="font-size:16px;font-weight:600">Label Library</h1>
  <button onclick="toggleForm()" id="toggleBtn">+ Add Label</button>
</div>

<div id="addForm" style="display:none;border:0.5px solid #d0d0d0;padding:20px;margin-bottom:24px;max-width:480px">
  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
      <div>
        <label class="field-label">Label Name</label>
        <input type="text" name="name" placeholder="e.g. Eclipse" required>
      </div>
      <div>
        <label class="field-label">Code / Number</label>
        <input type="text" name="code" placeholder="e.g. 02" maxlength="10" required>
      </div>
    </div>
    <div style="margin-bottom:16px">
      <label class="field-label">Image File (PNG or JPEG)</label>
      <input type="file" name="img" accept=".png,.jpg,.jpeg" required>
      <div style="font-size:11px;color:#999;margin-top:4px">Export at 300 DPI from Illustrator. Dimensions are read from the image metadata.</div>
    </div>
    <div style="display:flex;gap:8px">
      <button type="submit" class="primary">Save Label</button>
      <button type="button" onclick="toggleForm()">Cancel</button>
    </div>
  </form>
</div>

<?php if (empty($labels)): ?>
  <p style="color:#666">No labels yet. Add one above.</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse">
  <thead>
    <tr style="border-bottom:0.5px solid #d0d0d0">
      <th style="text-align:left;padding:8px 10px;font-weight:600;font-size:12px;color:#666">PREVIEW</th>
      <th style="text-align:left;padding:8px 10px;font-weight:600;font-size:12px;color:#666">NAME</th>
      <th style="text-align:left;padding:8px 10px;font-weight:600;font-size:12px;color:#666">CODE</th>
      <th style="text-align:left;padding:8px 10px;font-weight:600;font-size:12px;color:#666">DIMENSIONS</th>
      <th style="text-align:left;padding:8px 10px;font-weight:600;font-size:12px;color:#666">ADDED</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($labels as $l): ?>
    <tr style="border-bottom:0.5px solid #e0e0e0">
      <td style="padding:8px 10px">
        <div style="width:60px;height:40px;border:0.5px solid #e0e0e0;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#f5f5f5">
          <img src="<?= htmlspecialchars($l['svg_path']) ?>" style="max-width:58px;max-height:38px;object-fit:contain" alt="">
        </div>
      </td>
      <td style="padding:8px 10px;font-weight:500"><?= htmlspecialchars($l['name']) ?></td>
      <td style="padding:8px 10px;color:#666"><?= htmlspecialchars($l['code']) ?></td>
      <td style="padding:8px 10px;color:#666"><?= $l['width_mm'] ?> &times; <?= $l['height_mm'] ?> mm</td>
      <td style="padding:8px 10px;color:#999;font-size:12px"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
      <td style="padding:8px 10px;text-align:right">
        <a href="labels.php?delete=<?= $l['id'] ?>" onclick="return confirm('Delete this label?')">
          <button class="danger" style="font-size:12px">Delete</button>
        </a>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>

<script>
function toggleForm() {
  const f = document.getElementById('addForm');
  const b = document.getElementById('toggleBtn');
  if (f.style.display === 'none') { f.style.display='block'; b.textContent='✕ Cancel'; }
  else { f.style.display='none'; b.textContent='+ Add Label'; }
}
<?php if ($msg): ?>window.addEventListener('DOMContentLoaded',()=>{ document.getElementById('addForm').style.display='block'; document.getElementById('toggleBtn').textContent='✕ Cancel'; });<?php endif ?>
</script>
<?php pageFooter(); ?>
