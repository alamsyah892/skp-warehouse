<?php

namespace App\Support;

class IndonesianNumberToWords
{
    /**
     * @var array<int, string>
     */
    private const UNITS = [
        '',
        'Satu',
        'Dua',
        'Tiga',
        'Empat',
        'Lima',
        'Enam',
        'Tujuh',
        'Delapan',
        'Sembilan',
        'Sepuluh',
        'Sebelas',
    ];

    /**
     * @var array<int, string>
     */
    private const SCALES = [
        1_000_000_000_000 => 'Triliun',
        1_000_000_000 => 'Miliar',
        1_000_000 => 'Juta',
        1_000 => 'Ribu',
    ];

    public static function rupiah(float|int|string|null $amount): string
    {
        $number = (int) round((float) $amount);

        if ($number === 0) {
            return 'Nol Rupiah';
        }

        if ($number < 0) {
            return 'Minus '.self::rupiah(abs($number));
        }

        return trim(self::spell($number)).' Rupiah';
    }

    public static function spell(int $number): string
    {
        if ($number < 12) {
            return self::UNITS[$number];
        }

        if ($number < 20) {
            return self::spell($number - 10).' Belas';
        }

        if ($number < 100) {
            return self::spell(intdiv($number, 10)).' Puluh'.self::suffix($number % 10);
        }

        if ($number < 200) {
            return 'Seratus'.self::suffix($number - 100);
        }

        if ($number < 1_000) {
            return self::spell(intdiv($number, 100)).' Ratus'.self::suffix($number % 100);
        }

        if ($number < 2_000) {
            return 'Seribu'.self::suffix($number - 1_000);
        }

        foreach (self::SCALES as $value => $label) {
            if ($number >= $value) {
                return self::spell(intdiv($number, $value)).' '.$label.self::suffix($number % $value);
            }
        }

        return '';
    }

    private static function suffix(int $number): string
    {
        return $number > 0 ? ' '.self::spell($number) : '';
    }
}
