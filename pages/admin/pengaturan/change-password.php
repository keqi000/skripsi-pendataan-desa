<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['old_password']) || !isset($input['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $queries = new Queries();
    
    // Get current user
    $stmt = $queries->db->prepare("SELECT password FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Verify old password
    if (!password_verify($input['old_password'], $user['password'])) {
        throw new Exception('Password lama tidak benar');
    }
    
    // Update password
    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt = $queries->db->prepare("UPDATE user SET password = ? WHERE id_user = ?");
    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>