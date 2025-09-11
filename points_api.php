<?php
// points_api.php — Read-only API titik aktif untuk user yg login
session_start();
require 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// Boleh diakses siapa saja yang sudah login (user/admin/super_admin)
if (!isset($_SESSION['id_user'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit();
}

$action = $_GET['action'] ?? 'getActive';

if ($action === 'getActive') {
  $sql = "SELECT id, name, type, phone, address, lat, lng, active, updated_at
          FROM location_points
          WHERE active = 1
          ORDER BY id DESC";
  $res = $conn->query($sql);
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  echo json_encode($rows);
  exit();
}

echo json_encode(['error' => 'Invalid action']);
