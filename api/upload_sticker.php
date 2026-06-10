<?php
header('Content-Type: application/json');
$file = $_FILES['sticker'] ?? null;
if (!$file) { echo json_encode(['path'=>null,'error'=>'no file received']); exit; }
if ($file['error'] !== 0) { echo json_encode(['path'=>null,'error'=>'upload error '.$file['error']]); exit; }
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['png','jpg','jpeg'])) { echo json_encode(['path'=>null,'error'=>'bad ext']); exit; }
$dir  = realpath(__DIR__ . '/../uploads/svgs');
$dest = $dir . DIRECTORY_SEPARATOR . 'stk_' . uniqid('',true) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dest)) { echo json_encode(['path'=>null,'error'=>'move failed']); exit; }
echo json_encode(['path'=>$dest]);
