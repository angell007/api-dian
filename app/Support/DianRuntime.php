<?php

namespace App\Support;

/**
 * Ajustes de runtime para generación XML / envío DIAN (memoria, etc.).
 */
class DianRuntime
{
    public static function applyMemoryLimit(): void
    {
        $limit = config('dian_debug.memory_limit', env('DIAN_MEMORY_LIMIT', '512M'));
        if (!is_string($limit) || trim($limit) === '') {
            return;
        }
        $limit = trim($limit);
        $currentBytes = self::parseBytes((string) ini_get('memory_limit'));
        $targetBytes = self::parseBytes($limit);
        if ($targetBytes > 0 && ($currentBytes <= 0 || $targetBytes > $currentBytes)) {
            ini_set('memory_limit', $limit);
        }
    }

    private static function parseBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        if ($unit === 'g') {
            return (int) ($number * 1024 * 1024 * 1024);
        }
        if ($unit === 'm') {
            return (int) ($number * 1024 * 1024);
        }
        if ($unit === 'k') {
            return (int) ($number * 1024);
        }
        return (int) $number;
    }
}
