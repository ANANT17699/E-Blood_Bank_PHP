<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Enquiry');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        die("Name, email, and message are required.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (user_id, name, email, phone, subject, message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $name, $email, $phone, $subject, $message]);

        header("Location: contact.html?msg=" . urlencode("Thank you! Your message has been saved in our database. We will get back to you shortly."));
        exit();
    } catch (PDOException $e) {
        die("Failed to send message: " . $e->getMessage());
    }
}
?>
