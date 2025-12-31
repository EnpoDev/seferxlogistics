<?php

namespace App\Helpers;

class PhoneFormatter
{
    /**
     * Telefon numarasini formatla (Turkiye formati)
     * Ornek: 05536249910 -> 0553 624 99 10
     */
    public static function format(?string $phone): string
    {
        if (!$phone) {
            return '-';
        }

        // Sadece rakamlari al
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Turkiye telefon numarasi (10 veya 11 hane)
        if (strlen($digits) === 10) {
            // 5XX XXX XX XX -> 05XX XXX XX XX
            return '0' . substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 2) . ' ' . substr($digits, 8, 2);
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            // 05XX XXX XX XX
            return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 2) . ' ' . substr($digits, 9, 2);
        } elseif (strlen($digits) === 12 && substr($digits, 0, 2) === '90') {
            // +90 5XX XXX XX XX
            return '+90 ' . substr($digits, 2, 3) . ' ' . substr($digits, 5, 3) . ' ' . substr($digits, 8, 2) . ' ' . substr($digits, 10, 2);
        }

        // Diger formatlar icin oldugu gibi dondur
        return $phone;
    }
}
