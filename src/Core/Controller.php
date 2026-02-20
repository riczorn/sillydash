<?php

namespace App\Core;

class Controller
{
    protected $view;
    protected $db;

    public function __construct()
    {
        $this->view = new View();
        if (file_exists(ROOTPATH . 'config.php')) {
            $this->db = Database::getInstance();
        }

        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Render a view
     */
    protected function view($path, $data = [])
    {
        // Extract data to make it available in view
        if (!empty($data)) {
            extract($data);
        }

        // Output buffering to capture view
        // The View class does this usually, but let's see how our View class is implemented
        // The current View::render returns string.
        echo $this->view->render($path, $data);
    }

    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     */
    protected function redirect($url)
    {
        // If url doesn't start with http, assume it might need site_url if not already processed
        // But usually the caller should use site_url().
        // Let's rely on caller using site_url() or passing full path.
        // However, checks in Home.php were passing '/setup', which is absolute path on domain.
        // If we want relative to app, we should use site_url.

        header("Location: " . $url);
        exit;
    }

    /**
     * Get POST data
     */
    protected function getPost($key = null)
    {
        if ($key === null)
            return $_POST;
        return $_POST[$key] ?? null;
    }

    /**
     * Get GET data
     */
    protected function getQuery($key = null)
    {
        if ($key === null)
            return $_GET;
        return $_GET[$key] ?? null;
    }

    /**
     * Get Session data
     */
    protected function getSession($key = null)
    {
        if ($key === null)
            return $_SESSION;
        return $_SESSION[$key] ?? null;
    }

    /**
     * Set Session data
     */
    protected function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Remove Session data
     */
    protected function removeSession($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy Session
     */
    protected function destroySession()
    {
        session_destroy();
    }
}
