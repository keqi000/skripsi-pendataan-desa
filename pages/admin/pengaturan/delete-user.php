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

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

try {
    $queries = new Queries();
    
    // Prevent self-deletion
    if ($input['user_id'] == $_SESSION['user_id']) {
        throw new Exception('Tidak dapat menghapus akun sendiri');
    }
    
    // Delete user
    $stmt = $queries->db->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->execute([$input['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('User tidak ditemukan');
    }
    
    echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>