<?php
// Global Functions untuk sistem

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format date to Indonesian
function formatDateIndonesian($date) {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return $day . ' ' . $month . ' ' . $year;
}

// Check user permission
function checkPermission($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if ($required_role === 'admin_kecamatan' && $_SESSION['user_role'] !== 'admin_kecamatan') {
        return false;
    }
    
    return true;
}

// Generate unique ID
function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . '_' . time();
}

// Validate NIK
function validateNIK($nik) {
    return preg_match('/^[0-9]{16}$/', $nik);
}

// Get age from birth date
function calculateAge($birthDate) {
    $today = new DateTime();
    $birth = new DateTime($birthDate);
    return $today->diff($birth)->y;
}

// Upload file handler
function handleFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    if ($file_size > $max_size) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $new_filename = generateUniqueId('upload_') . '.' . $file_ext;
    $upload_path = UPLOAD_PATH . $new_filename;
    
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return [
            'success' => true, 
            'filename' => $new_filename,
            'url' => UPLOAD_URL . $new_filename
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Log activity
function logActivity($user_id, $action, $description) {
    global $db;
    
    try {
        $query = "INSERT INTO activity_log (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $action, $description]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

// Send JSON response
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit();
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return ['message' => $message, 'type' => $type];
    }
    
    return null;
}
?>