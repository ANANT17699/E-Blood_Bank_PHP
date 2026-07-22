<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;

    $blood_group = $_POST['blood_type'] ?? 'O+';
    $urgency = $_POST['urgency'] ?? 'urgent';
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = (int)($_POST['patient_age'] ?? 0);
    $units_required = (int)($_POST['units_required'] ?? 1);
    $needed_by_date = !empty($_POST['needed_by_date']) ? $_POST['needed_by_date'] : null;
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $hospital_contact = trim($_POST['hospital_contact'] ?? '');
    $medical_condition = trim($_POST['medical_condition'] ?? '');

    if (empty($patient_name) || empty($hospital_name) || empty($hospital_contact) || empty($city)) {
        die("Required fields missing. Please complete all required blood request fields.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO blood_requests 
            (user_id, patient_name, patient_age, relation, blood_group, units_required, needed_by_date, hospital_name, ward, city, hospital_contact, medical_condition, urgency, status)
            VALUES (?, ?, ?, 'Self', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $user_id,
            $patient_name,
            $patient_age,
            $blood_group,
            $units_required,
            $needed_by_date,
            $hospital_name,
            $ward,
            $city,
            $hospital_contact,
            $medical_condition,
            $urgency
        ]);

        header("Location: dashboard.php?msg=" . urlencode("Blood request submitted successfully!"));
        exit();
    } catch (PDOException $e) {
        die("Failed to record blood request: " . $e->getMessage());
    }
}
?>
