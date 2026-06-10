<?php
require 'db.php';
require 'layout.php';

$db  = getDB();
$msg = '';

if (isset($_GET['delete'])) {
    $row = $db->prepare('SELECT img_path FROM stickers WHERE id=?');
    $row->execute([(int)$_GET['delete']]);
    $r = $row->fetch();
    if ($r && file_exists($r['img_path'])) unlink($r['img_path']);
    $db->prepare('DELETE FROM stickers WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: stickers.php?deleted=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $file = $_FILES['img'] ?? null;
    if ($name && $file && $file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg'])) {
            $dest = 'uploads/svgs/stk_' . uniqid('',true) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dest);
            $db->prepare('INSERT INTO stickers (name,img_path) VALUES (?,?)')->execute([$name, $dest]);
            header('Location: stickers.php?added=1'); exit;
        } else { $msg = '<div class="msg err">Only PNG or JPEG accepted.</div>'; }
    } else { $msg = '<div class="msg err">All fields are required.</div>'; }
}

$stickers = $db->query('SELECT * FROM stickers ORDER BY created_at DESC')->fetchAll();
pageHeader('Sticker Library', 'stickers');
?>
<?php if (isset($_GET['added'])): ?><div class="msg ok">Sticker added.</div><?php endif ?>
<?php if (isset($_GET['deleted'])): ?><div class="msg ok">Sticker deleted.</div><?php endif ?>
<?= $msg ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1 style="font-size:16px;font-weight:600">Sticker Library <span style="font-size:12px;color:#999;font-weight:400">— 2 in &times; 0.87 in</span></h1>
  <button onclick="toggleForm()" id="toggleBtn">+ Add Sticker</button>
</div>

<div id="addForm" style="display:none;border:0.5px solid #d0d0d0;padding:20px;margin-bottom:24px;max-width:400px">
  <form method="POST" enctype="multipart/form-data">
    <div style="margin-bottom:12px">
      <label class="field-label">Sticker Name</label>
      <input type="text" name="name" placeholder="e.g. Blank White" required>
    </div>
    <div style="margin-bottom:16px">
      <label class="field-label">Image File (PNG or JPEG, 2&times;0.87 in @ 300 DPI)</label>
      <input type="file" name="img" accept=".png,.jpg,.jpeg" required>
    </div>
    <div style="display:flex;gap:8px">
      <button type="submit" class="primary">Save Sticker</button>
      <button type="button" onclick="toggleForm()">Cancel</button>
    </div>
  </form>
</div>

<?php if (empty($stickers)): ?>
  <p style="color:#666">No stickers yet. Add one above.</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse">
  <thead>
    <tr style="border-bottom:0.5px solid #d0d0d0">
      <th style="text-align:left;padding:8px 10px;font-size:12px;color:#666">PREVIEW</th>
      <th style="text-align:left;padding:8px 10px;font-size:12px;color:#666">NAME</th>
      <th style="text-align:left;padding:8px 10px;font-size:12px;color:#666">ADDED</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($stickers as $s): ?>
    <tr style="border-bottom:0.5px solid #e0e0e0">
      <td style="padding:8px 10px">
        <div style="width:80px;height:35px;border:0.5px solid #e0e0e0;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#f5f5f5">
          <img src="<?= htmlspecialchars($s['img_path']) ?>" style="max-width:78px;max-height:33px;object-fit:contain" alt="">
        </div>
      </td>
      <td style="padding:8px 10px;font-weight:500"><?= htmlspecialchars($s['name']) ?></td>
      <td style="padding:8px 10px;color:#999;font-size:12px"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
      <td style="padding:8px 10px;text-align:right">
        <a href="stickers.php?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this sticker?')">
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
  if (f.style.display==='none') { f.style.display='block'; b.textContent='✕ Cancel'; }
  else { f.style.display='none'; b.textContent='+ Add Sticker'; }
}
</script>
<?php pageFooter(); ?>
