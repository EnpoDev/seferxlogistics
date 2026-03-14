<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoPrivateIpRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (!$host) {
            $fail('Gecersiz URL formatı.');
            return;
        }

        // Resolve hostname to IP
        $ip = gethostbyname($host);

        // If resolution failed, gethostbyname returns the hostname
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            $fail('URL hostname cozumlenemedi.');
            return;
        }

        // Block private and reserved IP ranges
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $fail('Dahili veya ozel ag adreslerine yonlendirme yapilamaz.');
            return;
        }

        // Block localhost variants
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) {
            $fail('Localhost adreslerine yonlendirme yapilamaz.');
            return;
        }

        // Block common cloud metadata endpoints
        if (in_array($host, ['169.254.169.254', 'metadata.google.internal'])) {
            $fail('Cloud metadata adreslerine erisim engellendi.');
            return;
        }
    }
}
