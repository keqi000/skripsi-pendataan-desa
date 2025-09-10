<?php
// Authentication helper functions

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'pages/auth/login.php');
        exit();
    }
}

function requireRole($required_role) {
    requireLogin();
    
    if ($_SESSION['user_role'] !== $required_role) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

function requireOperatorDesa() {
    requireRole('operator_desa');
}

function requireAdminKecamatan() {
    requireRole('admin_kecamatan');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isOperatorDesa() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'operator_desa';
}

function isAdminKecamatan() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin_kecamatan';
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'desa_id' => $_SESSION['user_desa'] ?? null,
        'desa_name' => $_SESSION['desa_name'] ?? null
    ];
}

function getUserDesa() {
    return $_SESSION['user_desa'] ?? null;
}

function canAccessDesa($desa_id) {
    if (isAdminKecamatan()) {
        return true; // Admin can access all desa
    }
    
    if (isOperatorDesa()) {
        return getUserDesa() == $desa_id;
    }
    
    return false;
}
?>