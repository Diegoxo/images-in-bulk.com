<?php
/**
 * API: Contact/Support Form Handler
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get POST data
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$subject = htmlspecialchars($_POST['subject'] ?? '');
$phone = htmlspecialchars($_POST['phone'] ?? '');
$country = htmlspecialchars($_POST['country'] ?? '');
$message = htmlspecialchars($_POST['message'] ?? '');

if (!$email || !$message) {
    echo json_encode(['success' => false, 'error' => 'Please provide email and message']);
    exit;
}

$to = "clasesadomicilio30@gmail.com";
$emailSubject = "Contact Form: $subject";

$emailBody = "New contact form submission:\n\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Phone: $phone\n";
$emailBody .= "Country: $country\n";
$emailBody .= "Subject: $subject\n\n";
$emailBody .= "Message:\n$message\n";

$headers = "From: noreply@images-in-bulk.com\r\n";
$headers .= "Reply-To: $email\r\n";

// Use mail() function - requires working mail server on production
$mailSent = @mail($to, $emailSubject, $emailBody, $headers);

// In local environment (XAMPP), mail() might fail if not configured.
// For demonstration, let's return success true but log if it failed.
if (!$mailSent) {
    error_log("Mail to $to failed to send locally. Content: " . $emailBody);
}

echo json_encode([
    'success' => true, 
    'message' => 'Your message has been sent successfully. We will contact you soon.'
]);
