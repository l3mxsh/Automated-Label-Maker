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
  header('Location: labels.php?deleted=1');
  exit;
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
        header('Location: labels.php?added=1');
        exit;
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
<style>
  .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px
  }

  .form-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 28px;
    margin-bottom: 32px;
    max-width: 520px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04)
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px
  }

  .form-row.full {
    grid-template-columns: 1fr;
    gap: 16px
  }

  .form-group {
    display: flex;
    flex-direction: column
  }

  .hint-text {
    font-size: 12px;
    color: #999;
    margin-top: 8px;
    line-height: 1.4
  }

  .table-wrapper {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04)
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 14px
  }

  .preview-box {
    width: 70px;
    height: 48px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f5f5f5
  }

  .preview-box img {
    max-width: 68px;
    max-height: 46px;
    object-fit: contain
  }

  .action-cell {
    text-align: right
  }

  button#toggleBtn {
    gap: 6px
  }
</style>

<?php if (isset($_GET['added'])): ?><div class="msg ok">Label added successfully.</div><?php endif ?>
<?php if (isset($_GET['deleted'])): ?><div class="msg ok">Label deleted.</div><?php endif ?>
<?= $msg ?>

<div class="header-bar">
  <h1>Label Library</h1>
  <button onclick="toggleForm()" id="toggleBtn" class="primary">+ Add Label</button>
</div>

<div id="addForm" class="form-card" style="display:none">
  <form method="POST" enctype="multipart/form-data">
    <div class="form-row">
      <div class="form-group">
        <label class="field-label">Label Name</label>
        <input type="text" name="name" placeholder="e.g. Eclipse" required>
      </div>
      <div class="form-group">
        <label class="field-label">Code / Number</label>
        <input type="text" name="code" placeholder="e.g. 02" maxlength="10" required>
      </div>
    </div>
    <div class="form-row full">
      <div class="form-group">
        <label class="field-label">Image File (PNG or JPEG)</label>
        <input type="file" name="img" accept=".png,.jpg,.jpeg" required>
        <div class="hint-text">Export at 300 DPI from Illustrator. Dimensions are read from the image metadata.</div>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:24px">
      <button type="submit" class="primary">Save Label</button>
      <button type="button" onclick="toggleForm()">Cancel</button>
    </div>
  </form>
</div>

<?php if (empty($labels)): ?>
  <div class="empty-state">No labels yet. Add one to get started.</div>
<?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Preview</th>
          <th>Name</th>
          <th>Code</th>
          <th>Dimensions</th>
          <th>Added</th>
          <th class="action-cell">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($labels as $l): ?>
          <tr>
            <td>
              <div class="preview-box">
                <img src="<?= htmlspecialchars($l['svg_path']) ?>" alt="">
              </div>
            </td>
            <td><?= htmlspecialchars($l['name']) ?></td>
            <td><span style="color:#999"><?= htmlspecialchars($l['code']) ?></span></td>
            <td><span style="color:#999"><?= $l['width_mm'] ?> × <?= $l['height_mm'] ?> mm</span></td>
            <td><span style="color:#999;font-size:12px"><?= date('d M Y', strtotime($l['created_at'])) ?></span></td>
            <td class="action-cell">
              <a href="labels.php?delete=<?= $l['id'] ?>" onclick="return confirm('Delete this label?')">
                <button class="danger" style="font-size:12px">Delete</button>
              </a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>

<script>
  function toggleForm() {
    const f = document.getElementById('addForm');
    const b = document.getElementById('toggleBtn');
    if (f.style.display === 'none') {
      f.style.display = 'block';
      b.textContent = '× Cancel';
    } else {
      f.style.display = 'none';
      b.textContent = '+ Add Label';
    }
  }
  <?php if ($msg): ?>window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('addForm').style.display = 'block';
    document.getElementById('toggleBtn').textContent = '× Cancel';
  });
  <?php endif ?>
</script>
<?php pageFooter(); ?>