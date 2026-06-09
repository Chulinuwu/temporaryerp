<?php
/**
 * PEGASUS ERP - Base Controller
 * All controllers should extend this class
 */

class Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Render a view inside the layout
     * $view is a path relative to views/ (e.g. 'items/index')
     * $data is an associative array of variables available to the view
     */
    protected function render($view, $data = [])
    {
        // Extract data to individual variables for use in the view
        extract($data);

        // Capture the view output
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Include the layout, which should echo $content
        $layoutFile = __DIR__ . '/../views/layout/app.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Redirect to a URL
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Return a JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Get the currently authenticated user
     */
    protected function getCurrentUser()
    {
        return Auth::user();
    }

    /**
     * Require authentication; redirect to login if not authenticated
     */
    protected function requireAuth()
    {
        if (!Auth::check()) {
            flash('error', 'Please log in to continue.');
            $this->redirect('/login');
        }
    }

    /**
     * Require one or more roles; show 403 if unauthorized
     * $roles can be a string or array of role names
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();

        if (is_string($roles)) {
            $roles = [$roles];
        }

        $hasRole = false;
        foreach ($roles as $role) {
            if (Auth::hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../views/errors/403.php')) {
                require __DIR__ . '/../views/errors/403.php';
            } else {
                echo '<h1>403 - Access Denied</h1>';
            }
            exit;
        }
    }

    /**
     * Require access to a specific module section.
     * Uses Auth::canAccess() which checks role-based permissions.
     *
     * @param string $section  e.g. 'sales', 'accounting', 'hr', 'production'
     */
    protected function requireAccess(string $section)
    {
        $this->requireAuth();

        if (!Auth::canAccess($section)) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../views/errors/403.php')) {
                require __DIR__ . '/../views/errors/403.php';
            } else {
                echo '<div style="text-align:center;padding:60px;font-family:sans-serif;">';
                echo '<h1 style="color:#c00;">403 - Access Denied</h1>';
                echo '<p>You do not have permission to access this section.</p>';
                echo '<a href="/dashboard" style="color:#003366;">Back to Dashboard</a>';
                echo '</div>';
            }
            exit;
        }
    }

    /**
     * Get POST data with optional default
     */
    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Get all POST data
     */
    protected function allInput()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Validate CSRF token from POST data
     */
    protected function validateCsrf()
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!csrf_verify($token)) {
            http_response_code(403);
            throw new RuntimeException('CSRF token validation failed.');
        }
    }
}
