<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        $smtpHost = (string) env('SMTP_HOST', '');

        if ($smtpHost !== '') {
            return $this->sendSmtp($to, $subject, $body, $replyToEmail, $replyToName);
        }

        $from = env('MAIL_FROM', 'no-reply@localhost');
        $headers = [
            'From: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        if ($replyToEmail) {
            $headers[] = 'Reply-To: ' . $replyToEmail;
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private function sendSmtp(
        string $to,
        string $subject,
        string $body,
        ?string $replyToEmail,
        ?string $replyToName
    ): bool {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) env('SMTP_HOST');
        $mail->Port = (int) env('SMTP_PORT', 587);
        $mail->SMTPAuth = true;
        $mail->Username = (string) env('SMTP_USERNAME');
        $mail->Password = (string) env('SMTP_PASSWORD');
        $mail->SMTPSecure = strtolower((string) env('SMTP_ENCRYPTION', 'tls')) === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->CharSet = 'UTF-8';

        $from = (string) env('MAIL_FROM', env('SMTP_USERNAME', 'no-reply@localhost'));
        $fromName = (string) env('MAIL_FROM_NAME', 'RQCode');
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        if ($replyToEmail) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $body;

        return $mail->send();
    }
}
