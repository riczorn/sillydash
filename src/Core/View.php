<?php

namespace App\Core;

class View
{
    public function render($viewPath, $data = [])
    {
        extract($data);

        $viewFile = __DIR__ . '/../Views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        ob_start();
        require $viewFile;
        return ob_get_clean();
    }
}
