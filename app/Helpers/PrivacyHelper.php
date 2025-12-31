<?php

namespace App\Helpers;

/**
 * KVKK/GDPR Uyumlu Kişisel Veri Maskeleme Helper'ı
 *
 * Bu sınıf, hassas kişisel verilerin (PII - Personally Identifiable Information)
 * güvenli bir şekilde maskelenmesini sağlar.
 *
 * KVKK Madde 4: Kişisel verilerin işlenmesinde uyulacak ilkeler
 * GDPR Article 5: Principles relating to processing of personal data
 *
 * @package App\Helpers
 * @version 1.0.0
 * @since 2025-01-01
 */
class PrivacyHelper
{
    /**
     * Telefon numarasını maskele
     * +90 532 123 4567 -> +90 5** *** **67
     *
     * @param string|null $phone
     * @param bool $showLast Sadece son 2 haneyi göster
     * @return string
     */
    public static function maskPhone(?string $phone, bool $showLast = true): string
    {
        if (empty($phone)) {
            return '-';
        }

        // Sadece rakamları al
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($digits);

        if ($length < 4) {
            return str_repeat('*', $length);
        }

        if ($showLast) {
            // Son 2 hane görünür: +90 5** *** **67
            $visible = substr($digits, -2);
            $masked = str_repeat('*', $length - 2);

            // Formatlı döndür
            if ($length >= 10) {
                return '+90 ' . substr($masked, 0, 3) . ' ' . substr($masked, 3, 3) . ' ' . substr($masked, 6, 2) . $visible;
            }
            return $masked . $visible;
        }

        // Tamamen maskele
        return str_repeat('*', $length);
    }

    /**
     * TC Kimlik numarasını maskele
     * 12345678901 -> 123******01
     *
     * @param string|null $tcNo
     * @return string
     */
    public static function maskTcNo(?string $tcNo): string
    {
        if (empty($tcNo)) {
            return '-';
        }

        $digits = preg_replace('/[^0-9]/', '', $tcNo);

        if (strlen($digits) !== 11) {
            return str_repeat('*', strlen($digits));
        }

        // İlk 3 ve son 2 hane görünür
        return substr($digits, 0, 3) . str_repeat('*', 6) . substr($digits, -2);
    }

    /**
     * E-posta adresini maskele
     * john.doe@example.com -> j***e@e***e.com
     *
     * @param string|null $email
     * @return string
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '-';
        }

        $parts = explode('@', $email);
        $local = $parts[0];
        $domain = $parts[1];

        // Local kısmını maskele
        $localLength = strlen($local);
        if ($localLength <= 2) {
            $maskedLocal = str_repeat('*', $localLength);
        } else {
            $maskedLocal = $local[0] . str_repeat('*', $localLength - 2) . $local[$localLength - 1];
        }

        // Domain kısmını maskele
        $domainParts = explode('.', $domain);
        $domainName = $domainParts[0];
        $domainExt = implode('.', array_slice($domainParts, 1));

        $domainLength = strlen($domainName);
        if ($domainLength <= 2) {
            $maskedDomain = str_repeat('*', $domainLength);
        } else {
            $maskedDomain = $domainName[0] . str_repeat('*', $domainLength - 2) . $domainName[$domainLength - 1];
        }

        return $maskedLocal . '@' . $maskedDomain . '.' . $domainExt;
    }

    /**
     * Adresi maskele (Sadece mahalle/ilçe görünür)
     * Atatürk Mah. 123 Sok. No:5 Kadıköy/İstanbul -> ****** Kadıköy/İstanbul
     *
     * @param string|null $address
     * @return string
     */
    public static function maskAddress(?string $address): string
    {
        if (empty($address)) {
            return '-';
        }

        // İlçe/İl bilgisini bulmaya çalış (/ ile ayrılmış son kısım)
        if (preg_match('/([^\/]+\/[^\/]+)$/', $address, $matches)) {
            $district = trim($matches[1]);
            return '****** ' . $district;
        }

        // Alternatif: Son kelimeyi göster
        $words = explode(' ', $address);
        if (count($words) > 2) {
            return '****** ' . end($words);
        }

        return str_repeat('*', min(20, strlen($address)));
    }

    /**
     * IBAN maskele
     * TR12 3456 7890 1234 5678 9012 34 -> TR12 **** **** **** **** **** 34
     *
     * @param string|null $iban
     * @return string
     */
    public static function maskIban(?string $iban): string
    {
        if (empty($iban)) {
            return '-';
        }

        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        $length = strlen($iban);

        if ($length < 8) {
            return str_repeat('*', $length);
        }

        // İlk 4 ve son 2 karakter görünür
        $visible = substr($iban, 0, 4) . str_repeat(' ****', 5) . ' ' . substr($iban, -2);
        return $visible;
    }

    /**
     * Vergi numarasını maskele
     * 1234567890 -> 12******90
     *
     * @param string|null $taxNo
     * @return string
     */
    public static function maskTaxNumber(?string $taxNo): string
    {
        if (empty($taxNo)) {
            return '-';
        }

        $digits = preg_replace('/[^0-9]/', '', $taxNo);
        $length = strlen($digits);

        if ($length < 4) {
            return str_repeat('*', $length);
        }

        return substr($digits, 0, 2) . str_repeat('*', $length - 4) . substr($digits, -2);
    }

    /**
     * İsmi maskele (Sadece baş harfler)
     * Ahmet Yılmaz -> A*** Y*****
     *
     * @param string|null $name
     * @return string
     */
    public static function maskName(?string $name): string
    {
        if (empty($name)) {
            return '-';
        }

        $words = explode(' ', trim($name));
        $masked = [];

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $masked[] = mb_substr($word, 0, 1) . str_repeat('*', max(0, mb_strlen($word) - 1));
            }
        }

        return implode(' ', $masked);
    }

    /**
     * Plaka numarasını maskele
     * 34 ABC 123 -> 34 *** ***
     *
     * @param string|null $plate
     * @return string
     */
    public static function maskPlate(?string $plate): string
    {
        if (empty($plate)) {
            return '-';
        }

        // İl kodunu al (ilk 2 karakter)
        $plate = strtoupper(preg_replace('/\s+/', ' ', trim($plate)));

        if (preg_match('/^(\d{2})\s*(.+)$/', $plate, $matches)) {
            return $matches[1] . ' ***';
        }

        return '** ***';
    }

    /**
     * IP adresini maskele
     * 192.168.1.100 -> 192.168.*.*
     *
     * @param string|null $ip
     * @return string
     */
    public static function maskIpAddress(?string $ip): string
    {
        if (empty($ip)) {
            return '-';
        }

        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.*.*';
        }

        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return $parts[0] . ':' . $parts[1] . ':****:****:****:****:****:****';
        }

        return '*.*.*.* ';
    }

    /**
     * Log için hassas verileri temizle
     * Array içindeki tüm hassas alanları maskeler
     *
     * @param array $data
     * @return array
     */
    public static function sanitizeForLogging(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'current_password',
            'tc_no', 'tcno', 'tc_kimlik', 'kimlik_no',
            'iban', 'account_number', 'card_number',
            'cvv', 'cvc', 'expiry',
            'api_key', 'api_secret', 'secret', 'token',
            'customer_phone', 'phone', 'tel', 'mobile',
            'customer_address', 'address', 'adres',
            'email', 'e-mail',
            'tax_number', 'vergi_no',
            'ip', 'ip_address',
        ];

        $result = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            if (is_array($value)) {
                $result[$key] = self::sanitizeForLogging($value);
            } elseif (in_array($lowerKey, $sensitiveFields)) {
                // Hassas alan - maskele
                $result[$key] = self::autoMask($lowerKey, $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Alan tipine göre otomatik maskeleme
     *
     * @param string $fieldName
     * @param mixed $value
     * @return string
     */
    private static function autoMask(string $fieldName, $value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '[REDACTED]';
        }

        $value = (string) $value;

        return match (true) {
            str_contains($fieldName, 'phone') || str_contains($fieldName, 'tel') || str_contains($fieldName, 'mobile')
                => self::maskPhone($value),
            str_contains($fieldName, 'tc') || str_contains($fieldName, 'kimlik')
                => self::maskTcNo($value),
            str_contains($fieldName, 'email') || str_contains($fieldName, 'mail')
                => self::maskEmail($value),
            str_contains($fieldName, 'address') || str_contains($fieldName, 'adres')
                => self::maskAddress($value),
            str_contains($fieldName, 'iban') || str_contains($fieldName, 'account')
                => self::maskIban($value),
            str_contains($fieldName, 'tax') || str_contains($fieldName, 'vergi')
                => self::maskTaxNumber($value),
            str_contains($fieldName, 'ip')
                => self::maskIpAddress($value),
            str_contains($fieldName, 'password') || str_contains($fieldName, 'secret') ||
            str_contains($fieldName, 'token') || str_contains($fieldName, 'key')
                => '[REDACTED]',
            default => mb_substr($value, 0, 2) . str_repeat('*', max(0, mb_strlen($value) - 2)),
        };
    }

    /**
     * Kullanıcının kendi verisini görüntüleme yetkisi var mı kontrol et
     *
     * @param int $dataOwnerId Verinin sahibi
     * @param int|null $viewerId Görüntüleyen kullanıcı
     * @param array $authorizedRoles Maskelenmeden görebilecek roller
     * @return bool
     */
    public static function canViewUnmasked(int $dataOwnerId, ?int $viewerId, array $authorizedRoles = []): bool
    {
        // Kullanıcı yoksa maskelenmeli
        if ($viewerId === null) {
            return false;
        }

        // Kullanıcı kendi verisini görüyorsa maskelenmemeli
        if ($dataOwnerId === $viewerId) {
            return true;
        }

        // Yetkili roller kontrol
        $user = auth()->user();
        if ($user && !empty($authorizedRoles)) {
            $userRoles = $user->roles ?? [];
            if (array_intersect($userRoles, $authorizedRoles)) {
                return true;
            }
        }

        return false;
    }
}
