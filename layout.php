<?php
function pageHeader(string $title, string $active): void
{ ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — OVXI Label Positioner</title>
    <style>
      *,
      *::before,
      *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0
      }

      html {
        scroll-behavior: smooth
      }

      body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #1a1a1a;
        background: #fafafa
      }

      a {
        color: #000;
        text-decoration: none;
        transition: color 0.2s
      }

      a:hover {
        color: #555
      }

      nav {
        display: flex;
        align-items: center;
        gap: 0;
        border-bottom: 1px solid #e5e5e5;
        padding: 0 28px;
        height: 56px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05)
      }

      nav .brand {
        font-weight: 700;
        font-size: 15px;
        letter-spacing: 0.5px;
        margin-right: 40px;
        text-transform: uppercase;
        color: #000
      }

      nav a {
        padding: 0 16px;
        height: 56px;
        display: flex;
        align-items: center;
        font-size: 13px;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        color: #666
      }

      nav a.active {
        border-bottom-color: #000;
        color: #000
      }

      nav a:hover:not(.active) {
        color: #000;
        background: rgba(0, 0, 0, 0.02)
      }

      .page {
        padding: 32px;
        max-width: 1400px;
        margin: 0 auto
      }

      input,
      textarea,
      select {
        font-family: inherit;
        font-size: 13px;
        border: 1px solid #d9d9d9;
        padding: 8px 12px;
        outline: none;
        background: #fff;
        color: #1a1a1a;
        width: 100%;
        border-radius: 4px;
        transition: all 0.2s
      }

      input::placeholder,
      textarea::placeholder {
        color: #999
      }

      input:focus,
      textarea:focus,
      select:focus {
        border-color: #000;
        box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08)
      }

      button {
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid #d9d9d9;
        padding: 8px 16px;
        background: #fff;
        color: #1a1a1a;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center
      }

      button:hover:not(:disabled) {
        background: #f5f5f5;
        border-color: #999
      }

      button:active:not(:disabled) {
        background: #efefef
      }

      button.primary {
        background: #000;
        color: #fff;
        border-color: #000
      }

      button.primary:hover:not(:disabled) {
        background: #222;
        border-color: #222;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15)
      }

      button.primary:active:not(:disabled) {
        background: #111
      }

      button.danger {
        background: #fff;
        color: #1a1a1a;
        border-color: #d9d9d9
      }

      button.danger:hover:not(:disabled) {
        background: #f5f5f5;
        border-color: #999
      }

      button:disabled {
        opacity: 0.5;
        cursor: not-allowed
      }

      label.field-label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 6px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px
      }

      .msg {
        padding: 12px 16px;
        border: 1px solid #d9d9d9;
        font-size: 13px;
        margin-bottom: 20px;
        border-radius: 4px;
        background: #fff
      }

      .msg.ok {
        border-color: #d9d9d9;
        background: #f9f9f9;
        color: #1a1a1a
      }

      .msg.err {
        border-color: #e0e0e0;
        background: #fff;
        color: #d32f2f;
        font-weight: 500
      }

      h1 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 24px;
        color: #000;
        letter-spacing: -0.5px
      }

      h2 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #000
      }

      table {
        width: 100%;
        border-collapse: collapse
      }

      th {
        text-align: left;
        padding: 12px;
        font-size: 11px;
        font-weight: 700;
        color: #666;
        background: #f5f5f5;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e5e5e5
      }

      td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px
      }

      tr:hover {
        background: #fafafa
      }
    </style>
  </head>

  <body>
    <nav>
      <span class="brand">OVXI</span>
      <a href="generate.php" class="<?= $active === 'generate' ? 'active' : '' ?>">Generate PDF</a>
      <a href="labels.php" class="<?= $active === 'labels'  ? 'active' : '' ?>">Label Library</a>
      <a href="stickers.php" class="<?= $active === 'stickers' ? 'active' : '' ?>">Sticker Library</a>
    </nav>
    <div class="page">
    <?php }

  function pageFooter(): void
  { ?>
    </div>
  </body>

  </html>
<?php }
