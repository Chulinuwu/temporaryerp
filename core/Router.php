<?php
/**
 * PEGASUS ERP - Simple URL Router
 * Supports GET/POST with URL parameters like /items/{id}
 */

class Router
{
    private $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Register a GET route
     */
    public function get($path, $handler)
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    /**
     * Register a POST route
     */
    public function post($path, $handler)
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    /**
     * Dispatch the current request to the matching route handler
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            return $this->callHandler($this->routes[$method][$uri], []);
        }

        // Try pattern matching for parameterized routes
        foreach ($this->routes[$method] as $route => $handler) {
            $params = $this->matchRoute($route, $uri);
            if ($params !== false) {
                return $this->callHandler($handler, $params);
            }
        }

        // No route matched
        http_response_code(404);
        if (file_exists(__DIR__ . '/../views/errors/404.php')) {
            require __DIR__ . '/../views/errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    /**
     * Parse the request URI to get the clean path
     */
    private function getUri()
    {
        // Prefer PATH_INFO if available
        if (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            // Remove query string
            if (($pos = strpos($uri, '?')) !== false) {
                $uri = substr($uri, 0, $pos);
            }
            // Remove base path (in case app is in a subdirectory)
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            if ($scriptDir !== '/' && $scriptDir !== '\\') {
                $uri = substr($uri, strlen($scriptDir));
            }
        }

        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    /**
     * Match a route pattern against a URI and extract parameters
     * Returns parameter array on match, false otherwise
     */
    private function matchRoute($route, $uri)
    {
        // Convert route pattern like /items/{id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Filter to only named captures
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    /**
     * Call a route handler
     * Handler format: 'ControllerName@method' or callable
     */
    private function callHandler($handler, $params)
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, array_values($params));
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $methodName) = explode('@', $handler, 2);

            $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
            if (!file_exists($controllerFile)) {
                throw new RuntimeException("Controller file not found: {$controllerName}.php");
            }
            require_once $controllerFile;

            if (!class_exists($controllerName)) {
                throw new RuntimeException("Controller class not found: {$controllerName}");
            }

            $controller = new $controllerName();

            if (!method_exists($controller, $methodName)) {
                throw new RuntimeException("Method {$methodName} not found in {$controllerName}");
            }

            return call_user_func_array([$controller, $methodName], array_values($params));
        }

        throw new RuntimeException('Invalid route handler format.');
    }
}
