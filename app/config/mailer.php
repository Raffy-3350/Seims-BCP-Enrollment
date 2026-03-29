<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aedannearcilla377@gmail.com';
        $mail->Password   = 'dmxe iblf ohpx hztz';          // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // FIX: From address MUST match the Gmail account used for SMTP auth.
        // Gmail rejects emails where From != authenticated account.
        $mail->setFrom('aedannearcilla377@gmail.com', 'BCP SIEMS Notification');
        $mail->addReplyTo('no-reply@bcp.edu.ph', 'BCP SIEMS');  // cosmetic reply-to only

        // Recipients
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // plain-text fallback

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the actual error so you can debug it
        error_log('[Mailer Error] ' . $mail->ErrorInfo);
        return false;
    }
}
?>
