<?php
// mailer/mailer.php  –  Simple email sender (uses PHP mail())
// For production, replace with PHPMailer + SMTP for reliability.
require_once __DIR__ . '/../includes/config.php';

function sendVerificationEmail(string $toEmail, string $username, string $token): bool {
    $link    = SITE_URL . '/api/verify_email.php?token=' . urlencode($token);
    $subject = '[' . SITE_NAME . '] Verify your email address';
    $body    = "Hello $username,\n\n"
             . "Welcome to " . SITE_NAME . "!\n\n"
             . "Please click the link below to verify your email address:\n"
             . "$link\n\n"
             . "This link expires in 24 hours.\n\n"
             . "If you did not register, ignore this email.\n\n"
             . "– The " . SITE_NAME . " Team";

    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n"
             . "Reply-To: " . MAIL_FROM . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    return mail($toEmail, $subject, $body, $headers);
}

function sendPasswordResetEmail(string $toEmail, string $username, string $token): bool {
    $link    = SITE_URL . '/api/reset_password.php?token=' . urlencode($token);
    $subject = '[' . SITE_NAME . '] Password Reset Request';
    $body    = "Hello $username,\n\n"
             . "We received a request to reset your " . SITE_NAME . " password.\n\n"
             . "Click this link to set a new password (valid 1 hour):\n"
             . "$link\n\n"
             . "If you did not request this, ignore this email — your password is unchanged.\n\n"
             . "– The " . SITE_NAME . " Team";

    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n"
             . "Reply-To: " . MAIL_FROM . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    return mail($toEmail, $subject, $body, $headers);
}
