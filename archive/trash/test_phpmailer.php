<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'id.rms.for.us@gmail.com'; // Ganti dengan email pengirim
    $mail->Password   = 'rtsp utnv rlzd blpf';    // Ganti dengan App Password Gmail yang sudah Anda generate
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    //Recipients
    $mail->setFrom('id.rms.for.us@gmail.com', 'RMS Test');
    $mail->addAddress('habiburrazami@gmail.com', 'Habiburrohman Azzami'); // Ganti dengan email penerima

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Tes Email PHPMailer RMS';
    $mail->Body    = 'Ini adalah email uji coba PHPMailer dari aplikasi RMS.';
    $mail->AltBody = 'Ini adalah email uji coba PHPMailer dari aplikasi RMS.';

    $mail->send();
    echo '✅ Email berhasil dikirim via PHPMailer!';
} catch (Exception $e) {
    echo "❌ Email gagal dikirim. Error: {$mail->ErrorInfo}";
}
