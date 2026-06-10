<?php
require '../db.php';
header('Content-Type: application/json');
$labels = getDB()->query('SELECT id,name,code,svg_path,width_mm,height_mm FROM labels ORDER BY name')->fetchAll();
echo json_encode($labels);
