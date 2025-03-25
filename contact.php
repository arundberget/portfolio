<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// configuration
define('ADMIN_EMAIL', 'arundberget@ucmerced.edu');
define('EMAIL_COOLDOWN', 300); // 5 minutes in seconds

// initialize response array
        $response = array(
            'status' => 'error',
            'message' => ''
        );

// Debug information
$debug = array(
    'method' => $_SERVER["REQUEST_METHOD"],
    'post_data' => $_POST,
    'session' => $_SESSION
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log debug information
        error_log("Debug info: " . print_r($debug, true));
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
            $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $response['message'] = "Invalid request. Token mismatch or missing.";
            $response['debug'] = [
                'posted_token' => $_POST['csrf_token'] ?? 'none',
                'session_token' => $_SESSION['csrf_token'] ?? 'none'
            ];
            echo json_encode($response);
            exit;
        }

        // rate limiting
        if (isset($_SESSION['last_email_time']) &&
            time() - $_SESSION['last_email_time'] < EMAIL_COOLDOWN) {
            $response['message'] = "Please wait 5 minutes before sending another message.";
            echo json_encode($response);
            exit;
        }

        // get form data and sanitize
        $name = trim($_POST["name"] ?? '');
        $email = trim($_POST["email"] ?? '');
        $message = trim($_POST["message"] ?? '');
        // input validation
        if (empty($name) || empty($email) || empty($message)) {
            $response['message'] = "Please fill in all fields.";
            echo json_encode($response);
            exit;
    }

        // length validation
        if (strlen($name) > 100 || strlen($message) > 3000) {
            $response['message'] = "Input exceeds maximum length.";
            echo json_encode($response);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = "Invalid email address.";
            echo json_encode($response);
            exit;
        }

        // sanitization and encoding for security
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        // email headers
        $to = ADMIN_EMAIL;
        $subject = "Contact Form Submission from " . $name;
        $headers = "From: " . $email . "\r\n" .
                   "Reply-To: " . $email . "\r\n" .
                   "Content-type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        // email message
        $emailBody = "Name: " . $name . "\r\n" .
                    "Email: " . $email . "\r\n" .
                    "Message: " . $message . "\r\n" .
                    "Sent: " . date('Y-m-d H:i:s');
        // send email
        if (mail($to, $subject, $emailBody, $headers)) {
            // update last email time for rate limiting
            $_SESSION['last_email_time'] = time();

            $response['status'] = 'success';
            $response['message'] = "Thank you for your message! I will get back to you soon.";
            echo json_encode($response);
            exit;
        } else {
            error_log("Contact form mail error: " . error_get_last()['message']);
            $response['message'] = "There was an error sending your message. Please try again later.";
        echo json_encode($response);
        exit;
    }
    } catch (Exception $e) {
        error_log("Contact form exception: " . $e->getMessage());
        $response['message'] = "An unexpected error occurred. Please try again later.";
        $response['debug'] = $e->getMessage();
        echo json_encode($response);
        exit;
}
}

// Generate CSRF token for form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If this is a direct GET request to this file, return the CSRF token
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    echo $_SESSION['csrf_token'];
}
?>