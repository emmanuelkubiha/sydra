<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    public function send(string $to, string $subject, string $body): bool
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }

        return @mail($to, $subject, $body);
    }
}
