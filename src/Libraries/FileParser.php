<?php

namespace App\Libraries;

class FileParser
{
    public function parseFilename($filename)
    {
        // Example: virtualmin-2023-10-27.list
        // Example: sizes-2023-10-27.list
        // Example: mail-2023-10-27.list

        // Match name-YYYY-MM-DD_HH:MM.list OR name-YYYY-MM-DD.list
        if (preg_match('/^([a-z]+)-(\d{4}-\d{2}-\d{2})(?:_(\d{2}:\d{2}))?/', $filename, $matches)) {
            $type = $matches[1];
            $date = $matches[2];
            $time = isset($matches[3]) ? $matches[3] . ':00' : '00:00:00';

            return [
                'type' => $type,
                'generated_at' => "$date $time"
            ];
        }

        return null;
    }

    public function parseContent($content, $type)
    {
        if ($type === 'virtualmin') {
            return $this->parseVirtualminContent($content);
        }

        $lines = explode("\n", $content);
        $records = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            $this->parseSizeLine($line, $records);
        }

        return $records;
    }

    private function parseVirtualminContent($content)
    {
        $lines = explode("\n", $content);
        $records = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            if (strpos($line, 'DB:') === 0) {
                if ($current) {
                    $val = trim(substr($line, 3));
                    $current['db_names'] = rtrim($val, ',');
                }
            } elseif (strpos($line, 'Username:') === 0) {
                if ($current) {
                    $current['username'] = trim(substr($line, 9));
                }
            } elseif (strpos($line, 'Home directory:') === 0) {
                if ($current) {
                    $current['home_directory'] = trim(substr($line, 15));
                }
            } else {
                // New domain block
                if ($current) {
                    $records[] = $current;
                }
                $current = [
                    'domain' => $line,
                    'username' => '',
                    'home_directory' => '',
                    'db_names' => ''
                ];
            }
        }

        if ($current) {
            $records[] = $current;
        }

        return $records;
    }

    private function parseSizeLine($line, &$records)
    {
        // Format: SIZE /path/to/thing
        // Size can be 1234, 1.2M, 3G
        if (preg_match('/^(\S+)\s+(.+)$/', $line, $matches)) {
            $records[] = [
                'size_raw' => $matches[1],
                'path' => $matches[2]
            ];
        }
    }

    public function parseSizeBytes($sizeStr)
    {
        $units = ['B' => 0, 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4];
        $sizeStr = strtoupper($sizeStr);
        $unit = substr($sizeStr, -1);
        $number = substr($sizeStr, 0, -1);

        if (!isset($units[$unit])) {
            return (int) $sizeStr; // Assume bytes if no unit
        }

        $exponent = $units[$unit];
        return (int) ($number * pow(1024, $exponent));
    }
}
