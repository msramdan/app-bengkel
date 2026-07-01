<?php

namespace App\Support;

use InvalidArgumentException;

class StockReferenceValidator
{
    /** Prefixes reserved for system-generated document numbers. */
    private const RESERVED_PREFIXES = [
        'PBL-', 'JBL-', 'SRV-', 'TRX-', 'STM-', 'STK-',
    ];

    public static function assertManualReference(?string $referenceNo): void
    {
        if ($referenceNo === null || $referenceNo === '') {
            return;
        }

        $upper = strtoupper($referenceNo);

        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                throw new InvalidArgumentException(
                    'No. referensi tidak boleh memakai format nomor transaksi sistem ('.$prefix.'...).'
                );
            }
        }
    }
}
