<?php
function pageHeader(string $title, string $active): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> — OVXI Label Positioner</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;font-size:13px;color:#000;background:#fff}
a{color:#000;text-decoration:none}
nav{display:flex;align-items:center;gap:0;border-bottom:0.5px solid #d0d0d0;padding:0 20px;height:44px}
nav .brand{font-weight:600;font-size:14px;letter-spacing:.03em;margin-right:24px}
nav a{padding:0 14px;height:44px;display:flex;align-items:center;font-size:13px;border-bottom:1.5px solid transparent}
nav a.active{border-bottom-color:#000}
nav a:hover:not(.active){background:#f8f8f8}
.page{padding:24px}
input,textarea,select{font-family:inherit;font-size:13px;border:0.5px solid #d0d0d0;padding:6px 8px;outline:none;background:#fff;color:#000;width:100%}
input:focus,textarea:focus,select:focus{border-color:#999}
button{font-family:inherit;font-size:13px;border:0.5px solid #d0d0d0;padding:6px 12px;background:#fff;color:#000;cursor:pointer}
button:hover{background:#f5f5f5}
button.primary{background:#000;color:#fff;border-color:#000}
button.primary:hover{background:#222}
button.danger{color:#000;border-color:#d0d0d0}
button.danger:hover{background:#f5f5f5}
label.field-label{display:block;font-size:12px;color:#666;margin-bottom:4px}
.msg{padding:8px 12px;border:0.5px solid #d0d0d0;font-size:12px;margin-bottom:16px}
.msg.ok{border-color:#999}
.msg.err{border-color:#000;color:#000}
</style>
</head>
<body>
<nav>
  <span class="brand">OVXI</span>
  <a href="generate.php" class="<?= $active==='generate'?'active':'' ?>">Generate PDF</a>
  <a href="labels.php"   class="<?= $active==='labels'  ?'active':'' ?>">Label Library</a>
</nav>
<div class="page">
<?php }

function pageFooter(): void { ?>
</div>
</body>
</html>
<?php }
