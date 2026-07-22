<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: login.html?error=" . urlencode("Email and password are required."));
        exit();
    }

    try {
        // Query user record joining donor_details if available
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.password, u.role, u.phone, u.city,
                   d.blood_group
            FROM users u
            LEFT JOIN donor_details d ON u.id = d.user_id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Success: set session state
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['blood_group'] = $user['blood_group'] ?? 'O+';
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['city'] = $user['city'] ?? '';

            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: login.html?error=" . urlencode("Invalid email or password. Please try again."));
            exit();
        }
    } catch (PDOException $e) {
        die("Login process error: " . $e->getMessage());
    }
}
?>