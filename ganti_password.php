<?php
session_start();
require 'koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit();
}

$id_user = (int)$_SESSION['id_user'];
$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$old || !$new || !$confirm) {
    echo json_encode(['success'=>false,'message'=>'Semua field wajib diisi.']); exit();
}
if ($new !== $confirm) {
    echo json_encode(['success'=>false,'message'=>'Password baru dan konfirmasi tidak sama.']); exit();
}
if (strlen($new) < 6) {
    echo json_encode(['success'=>false,'message'=>'Password baru minimal 6 karakter.']); exit();
}

// cek password lama
$stmt = $conn->prepare("SELECT password FROM account WHERE id_user=? LIMIT 1");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($hashed);
if ($stmt->fetch()) {
    if (!password_verify($old, $hashed)) {
        echo json_encode(['success'=>false,'message'=>'Password lama salah.']); exit();
    }
} else {
    echo json_encode(['success'=>false,'message'=>'User tidak ditemukan.']); exit();
}
$stmt->close();

// update password baru
$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE account SET password=? WHERE id_user=?");
$stmt->bind_param("si", $newHash, $id_user);
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'✅ Password berhasil diganti.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Gagal mengganti password.']);
}
$stmt->close();
