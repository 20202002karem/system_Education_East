<?php

namespace App\Support;

/** BR-M2-01/02 normalization: trim, collapse inner whitespace, upper-case. */
class AssetIdentifier
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $value)));

        return $v === '' ? null : $v;
    }

    public static function isPlaceholderSerial(?string $value): bool
    {
        $n = self::normalize($value);
        if ($n === null) {
            return false;
        }
        $list = array_map(fn ($v) => self::normalize($v), (array) config('m2.placeholder_serials', []));

        return in_array($n, $list, true);
    }
}
