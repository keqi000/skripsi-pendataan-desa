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

$required = ['nama_lengkap', 'username', 'email', 'password', 'role'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field wajib diisi"]);
        exit();
    }
}

try {
    $queries = new Queries();
    
    // Check if username exists
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE username = ?");
    $stmt->execute([$input['username']]);
    if ($stmt->fetch()) {
        throw new Exception('Username sudah digunakan');
    }
    
    // Check if email exists
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Email sudah digunakan');
    }
    
    // Hash password
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $queries->db->prepare("
        INSERT INTO user (nama_lengkap, username, email, password, role, id_desa, status_aktif) 
        VALUES (?, ?, ?, ?, ?, ?, 'aktif')
    ");
    
    $stmt->execute([
        $input['nama_lengkap'],
        $input['username'],
        $input['email'],
        $hashedPassword,
        $input['role'],
        $input['id_desa']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User berhasil dibuat']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>