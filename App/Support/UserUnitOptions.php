<?php

namespace App\Support;

class UserUnitOptions
{
    /** @var array<int,string> */
    private const UNITS = [
        'UNIT AKUNTANSI & PAJAK',
        'UNIT SDM & UMUM',
        'UNIT PENJUALAN',
        'UNIT LOGISTIK',
        'UNIT MANAJEMEN RISIKO',
        'UNIT SEKRETARIS PERUSAHAAN',
        'UNIT STRATEGI BISNIS',
        'UNIT HUBUNGAN PRINSIPAL',
        'UNIT KEUANGAN',
        'UNIT QUALITY ASSURANCE & BPM',
        'UNIT TRADING & INSTITUSI',
        'UNIT NEW PRINCIPAL',
        'DIREKTORAT OPERASIONAL',
        'UNIT TEKNOLOGI INFORMASI',
        'UNIT SPI',
        'DIREKTORAT KEUANGAN, MAN.RIS & SDM',
    ];

    /** @return array<int,string> */
    public static function values(): array
    {
        return self::UNITS;
    }

    /** @return array<int,array{value:string,label:string}> */
    public static function options(): array
    {
        return array_map(fn (string $label) => [
            'value' => $label,
            'label' => $label,
        ], self::UNITS);
    }

    public static function isValid(?string $unit): bool
    {
        if ($unit === null || $unit === '') {
            return false;
        }

        return in_array($unit, self::UNITS, true);
    }
}
