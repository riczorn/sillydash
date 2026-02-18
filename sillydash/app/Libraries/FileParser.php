<?php

namespace App\Libraries;

class FileParser
{
    /**
     * Parses a size string (e.g., "1.2M", "500K", "1G") into Bytes (int).
     */
    public function parseSizeBytes(string $sizeStr): int
    {
        $sizeStr = trim($sizeStr);
        $unit = strtoupper(substr($sizeStr, -1));
        $value = (float) substr($sizeStr, 0, -1);

        if (is_numeric($sizeStr)) {
            return (int) $sizeStr;
        }

        switch ($unit) {
            case 'K':
                return (int) ($value * 1024);
            case 'M':
                return (int) ($value * 1024 * 1024);
            case 'G':
                return (int) ($value * 1024 * 1024 * 1024);
            case 'T':
                return (int) ($value * 1024 * 1024 * 1024 * 1024);
            default:
                return (int) $sizeStr;
        }
    }

    /**
     * Parses filename to extract type and timestamp.
     * Format: {type}-{YYYY-MM-DD_HH:MM}.list
     */
    public function parseFilename(string $filename): ?array
    {
        // Remove extension
        $nameObj = pathinfo($filename, PATHINFO_FILENAME); // spam-2026...

        // Regex with optional time part: (_HH:MM)?
        if (preg_match('/^([a-z0-9]+)-(\d{4}-\d{2}-\d{2})(_\d{2}:\d{2})?$/', $nameObj, $matches)) {
            $timePart = isset($matches[3]) ? $matches[3] : '_00:00';
            return [
                'type' => $matches[1],
                'generated_at' => str_replace('_', ' ', $matches[2] . $timePart) . ':00', // Add seconds
            ];
        }

        return null;
    }

    /**
     * Parses the content of a .list file based on its type.
     * 
     * @param string $content File content
     * @param string $type File type (virtualmin, db, log, mail, sizes, spam)
     * @return array Parsed data
     */
    public function parseContent(string $content, string $type): array
    {
        $lines = explode("\n", $content);
        $result = [];

        if ($type === 'virtualmin') {
            $currentDomain = null;
            $entry = [];

            foreach ($lines as $line) {
                if (empty(trim($line)))
                    continue;

                // Check indentation to distinguish domain from properties
                if (preg_match('/^\S/', $line)) {
                    // Start of new domain (no leading whitespace)
                    if ($currentDomain) {
                        $result[] = $entry;
                    }
                    $currentDomain = trim($line);
                    $entry = [
                        'domain' => $currentDomain,
                        'db_names' => '',
                        'username' => '',
                        'home_directory' => '',
                        'parent_domain' => null
                    ];
                } else {
                    // Property line
                    $parts = explode(':', trim($line), 2);
                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);

                        if ($key === 'DB')
                            $entry['db_names'] = $value;
                        if ($key === 'Username')
                            $entry['username'] = $value;
                        if ($key === 'Home directory')
                            $entry['home_directory'] = $value;
                        if ($key === 'Parent' || $key === 'Parent domain')
                            $entry['parent_domain'] = $value;
                    }
                }
            }
            // Add last one
            if ($currentDomain) {
                $result[] = $entry;
            }
        } else {
            // Standard size lists: {size}\t{path}
            foreach ($lines as $line) {
                if (empty(trim($line)))
                    continue;

                $parts = preg_split('/\s+/', trim($line), 2);
                if (count($parts) >= 2) {
                    $result[] = [
                        'size_raw' => $parts[0],
                        'path' => $parts[1]
                    ];
                }
            }
        }

        return $result;
    }
}
