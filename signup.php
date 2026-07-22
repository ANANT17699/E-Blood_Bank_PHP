<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'donor';
    if (!in_array($role, ['donor', 'requester'])) {
        $role = 'donor';
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        header("Location: signup.html?error=" . urlencode("All required fields must be filled."));
        exit();
    }

    if ($password !== $confirm_password && !empty($confirm_password)) {
        header("Location: signup.html?error=" . urlencode("Passwords do not match."));
        exit();
    }

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        header("Location: login.html?error=" . urlencode("An account with this email already exists. Please sign in."));
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Insert into users table
        $userStmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, phone, city, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $userStmt->execute([$first_name, $last_name, $email, $hashed_password, $role, $phone, $city, $address]);
        $user_id = $pdo->lastInsertId();

        $blood_group = 'Unknown';

        if ($role === 'donor') {
            // Extended donor details
            $blood_group = $_POST['blood_type'] ?? 'A+';
            $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
            $gender = $_POST['gender'] ?? 'prefer_not';
            $weight = !empty($_POST['weight']) ? (int)$_POST['weight'] : null;
            $last_donation = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : null;
            $total_donations = isset($_POST['total_donations']) ? (int)$_POST['total_donations'] : 0;
            $is_available = isset($_POST['available_donate']) ? 1 : 1;
            $notify_sms = isset($_POST['notify_sms']) ? 1 : 1;

            $donorStmt = $pdo->prepare("INSERT INTO donor_details (user_id, blood_group, dob, gender, weight, last_donation_date, total_donations, is_available, notify_sms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $donorStmt->execute([$user_id, $blood_group, $dob, $gender, $weight, $last_donation, $total_donations, $is_available, $notify_sms]);
        } elseif ($role === 'requester') {
            $blood_group = $_POST['blood_type_needed'] ?? 'O+';
            
            // If patient info was submitted during requester signup, insert initial blood request
            if (!empty($_POST['patient_name'])) {
                $patient_name = trim($_POST['patient_name']);
                $patient_age = (int)($_POST['patient_age'] ?? 30);
                $relation = trim($_POST['relation'] ?? 'Self');
                $units = (int)($_POST['units_required'] ?? 1);
                $hospital = trim($_POST['hospital'] ?? 'General Hospital');
                $medical_condition = trim($_POST['medical_condition'] ?? '');

                $reqStmt = $pdo->prepare("INSERT INTO blood_requests (user_id, patient_name, patient_age, relation, blood_group, units_required, hospital_name, city, hospital_contact, medical_condition, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $reqStmt->execute([$user_id, $patient_name, $patient_age, $relation, $blood_group, $units, $hospital, $city, $phone, $medical_condition]);
            }
        }

        $pdo->commit();

        // Establish session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $_SESSION['user_role'] = $role;
        $_SESSION['email'] = $email;
        $_SESSION['blood_group'] = $blood_group;

        header("Location: dashboard.php?welcome=1");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Registration failed: " . $e->getMessage());
    }
}
?>