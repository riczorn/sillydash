<?php

if (!function_exists('formatBytes')) {
    /**
     * Formats a byte value into a human-readable string (KB, MB, GB, TB).
     *
     * @param int|float $bytes     The size in bytes
     * @param int       $precision Decimal places (2 for TB, 1 for others)
     * @return string Formatted size string
     */
    function formatBytes($bytes, $precision = 1)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        if ($units[$pow] === 'TB') {
            $precision = 2;
        }

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
