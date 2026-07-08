<?php

declare(strict_types=1);

namespace App\Services;

final class Mailer
{
    public function send(string $to, string $subject, string $body): bool
    {
        $from = env('MAIL_FROM', 'no-reply@localhost');
        $headers = [
            'From: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
