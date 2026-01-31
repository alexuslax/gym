<?php
session_start();
require_once 'config/functions.php';

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, 'LOGOUT', 'users', ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (PDOException $e) {
        // Log error but don't prevent logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: index.php');
exit();
?>
