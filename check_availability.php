<?php
// check_availability.php — AJAX: check email uniqueness at signup
require_once 'includes/config.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'msg' => 'Invalid email']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM tblstudents WHERE email=?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['available' => false, 'msg' => 'Email already registered']);
} else {
    echo json_encode(['available' => true, 'msg' => 'Email is available']);
}
