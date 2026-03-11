<?php

require_once __DIR__ . '/../app/Services/LoggerService.php';

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->routes[] = [
            'method' => 'GET',
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function post($path, $handler)
    {
        $this->routes[] = [
            'method' => 'POST',
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch()
    {

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        if ($uri === '' || $uri === false) {
            $uri = '/';
        }

        foreach ($this->routes as $route) {

            $pattern = preg_replace('/\{[a-zA-Z]+\}/', '([^\/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (
                $_SERVER['REQUEST_METHOD'] === $route['method']
                && preg_match($pattern, $uri, $matches)
            ) {

                array_shift($matches);

                list($controller, $method) = explode('@', $route['handler']);

                try {

                    require_once __DIR__ . "/../app/Controllers/$controller.php";

                    $controllerInstance = new $controller();

                    call_user_func_array([$controllerInstance, $method], $matches);

                } catch (Throwable $e) {

                    LoggerService::error(
                        'router',
                        'controller_exception',
                        $e->getMessage(),
                        [
                            'controller' => $controller,
                            'method' => $method,
                            'uri' => $uri
                        ]
                    );

                    http_response_code(500);

                    echo "Internal Server Error";

                }

                return;
            }
        }

        # rota não encontrada

        LoggerService::warning(
            'router',
            'route_not_found',
            '404 route',
            [
                'uri' => $uri,
                'method' => $_SERVER['REQUEST_METHOD']
            ]
        );

        http_response_code(404);

        echo "404 Not Found";
    }
}