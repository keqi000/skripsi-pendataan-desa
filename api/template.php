<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/queries.php';
session_start();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$queries = new Queries();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Get template by ID
    $id = $_GET['id'];
    $query = "SELECT * FROM laporan_template WHERE id_template = ?";
    $stmt = $queries->db->prepare($query);
    $stmt->execute([$id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        echo json_encode(['success' => true, 'template' => $template]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete template
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'];
    
    $query = "DELETE FROM laporan_template WHERE id_template = ? AND created_by = ?";
    $stmt = $queries->db->prepare($query);
    $result = $stmt->execute([$id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>