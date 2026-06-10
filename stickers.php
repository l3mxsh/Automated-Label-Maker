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
  header('Location: stickers.php?deleted=1');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $file = $_FILES['img'] ?? null;
  if ($name && $file && $file['error'] === 0) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
      $dest = 'uploads/svgs/stk_' . uniqid('', true) . '.' . $ext;
      move_uploaded_file($file['tmp_name'], $dest);
      $db->prepare('INSERT INTO stickers (name,img_path) VALUES (?,?)')->execute([$name, $dest]);
      header('Location: stickers.php?added=1');
      exit;
    } else {
      $msg = '<div class="msg err">Only PNG or JPEG accepted.</div>';
    }
  } else {
    $msg = '<div class="msg err">All fields are required.</div>';
  }
}

$stickers = $db->query('SELECT * FROM stickers ORDER BY created_at DESC')->fetchAll();
pageHeader('Sticker Library', 'stickers');
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
    max-width: 480px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04)
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 16px
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
    width: 90px;
    height: 40px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f5f5f5
  }

  .preview-box img {
    max-width: 88px;
    max-height: 38px;
    object-fit: contain
  }

  .action-cell {
    text-align: right
  }

  button#toggleBtn {
    gap: 6px
  }
</style>

<?php if (isset($_GET['added'])): ?><div class="msg ok">Sticker added successfully.</div><?php endif ?>
<?php if (isset($_GET['deleted'])): ?><div class="msg ok">Sticker deleted.</div><?php endif ?>
<?= $msg ?>

<div class="header-bar">
  <div>
    <h1>Sticker Library</h1>
    <p style="font-size:13px;color:#999;margin-top:4px">Standard size: 2 in × 0.87 in</p>
  </div>
  <button onclick="toggleForm()" id="toggleBtn" class="primary">+ Add Sticker</button>
</div>

<div id="addForm" class="form-card" style="display:none">
  <form method="POST" enctype="multipart/form-data">
    <div class="form-row">
      <div class="form-group">
        <label class="field-label">Sticker Name</label>
        <input type="text" name="name" placeholder="e.g. Blank White" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="field-label">Image File (PNG or JPEG)</label>
        <input type="file" name="img" accept=".png,.jpg,.jpeg" required>
        <div class="hint-text">Size: 2 × 0.87 inches @ 300 DPI</div>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:24px">
      <button type="submit" class="primary">Save Sticker</button>
      <button type="button" onclick="toggleForm()">Cancel</button>
    </div>
  </form>
</div>

<?php if (empty($stickers)): ?>
  <div class="empty-state">No stickers yet. Add one to get started.</div>
<?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Preview</th>
          <th>Name</th>
          <th>Added</th>
          <th class="action-cell">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stickers as $s): ?>
          <tr>
            <td>
              <div class="preview-box">
                <img src="<?= htmlspecialchars($s['img_path']) ?>" alt="">
              </div>
            </td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><span style="color:#999;font-size:12px"><?= date('d M Y', strtotime($s['created_at'])) ?></span></td>
            <td class="action-cell">
              <a href="stickers.php?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this sticker?')">
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
      b.textContent = '+ Add Sticker';
    }
  }
</script>
<?php pageFooter(); ?>