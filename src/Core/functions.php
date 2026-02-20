<?php

use App\Core\Database;

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        // Detect protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Detect base URL from script name
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = str_replace('/index.php', '', $scriptName);

        if ($base === '') {
            $base = '/';
        } else {
            $base = rtrim($base, '/') . '/';
        }

        return $protocol . $host . $base . ltrim($path, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        return base_url($path);
    }
}

if (!function_exists('session')) {
    class SessionProxy
    {
        public function get($key)
        {
            return $_SESSION[$key] ?? null;
        }

        public function set($key, $value)
        {
            $_SESSION[$key] = $value;
        }

        public function getFlashdata($key)
        {
            // Simple flashdata implementation:
            // In a real framework, we'd mark it for deletion.
            return $_SESSION[$key] ?? null;
        }
    }

    function session()
    {
        return new SessionProxy();
    }
}

if (!function_exists('esc')) {
    function esc($str)
    {
        return htmlspecialchars((string) ($str ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
            }
        }
        return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return 'csrf_token';
    }
}

if (!function_exists('csrf_hash')) {
    function csrf_hash()
    {
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
            }
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('uri_string')) {
    function uri_string()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);

        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, strlen('/index.php'));
        }

        $uri = trim($uri, '/');
        // remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return $uri;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $value = $bytes / pow(1024, $pow);
        return round($value, $precision) . ' ' . $units[$pow];
    }
}
