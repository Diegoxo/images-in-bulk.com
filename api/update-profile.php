<?php
/**
 * API to update user profile information (Full Name).
 */
require_once '../includes/config.php';
require_once '../includes/utils/csrf.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Decode JSON body
$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$fullName = trim($data['full_name'] ?? '');

// Validation
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
    exit;
}

// Clean name
$fullName = preg_replace("/[^a-zA-Z\s]/", "", $fullName);
if (strlen($fullName) < 3) {
    echo json_encode(['success' => false, 'message' => 'Name is too short']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->execute([$fullName, $userId]);

    // Update Session
    $_SESSION['user_name'] = $fullName;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'new_name' => $fullName
    ]);
} catch (Exception $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
