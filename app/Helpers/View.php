<?php

class View {
    public static function render($view, $data = []) {
        // Extract data to make variables available in the view
        extract($data);

        // Path to the view file
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($viewPath)) {
            // Start output buffering
            ob_start();
            require_once $viewPath;
            $content = ob_get_clean();

            // Check if a layout should be used
            $layoutPath = __DIR__ . '/../Views/layouts/main.php';
            if (file_exists($layoutPath)) {
                require_once $layoutPath;
            } else {
                echo $content;
            }
        } else {
            die("View $view not found at $viewPath");
        }
    }
}
