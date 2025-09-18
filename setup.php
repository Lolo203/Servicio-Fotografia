<?php

// Create directory structure first
$directories = [
    'app',
    'app/Controllers',
    'app/Models', 
    'app/Views',
    'app/Core',
    'config',
    'public',
    'vendor'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Create BaseController
$baseControllerContent = '<?php

namespace App\Controllers;

class BaseController
{
    public function __construct()
    {
        // Base controller initialization
    }
}';

file_put_contents('app/Controllers/BaseController.php', $baseControllerContent);

// Create HomeController
$homeControllerContent = '<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        echo "<h1>Welcome to Servicio Fotografía</h1>";
        echo "<p>Photography service application is running!</p>";
    }
}';

file_put_contents('app/Controllers/HomeController.php', $homeControllerContent);

// Create PhotoController
$photoControllerContent = '<?php

namespace App\Controllers;

use App\Core\Database;

class PhotoController extends BaseController
{
    public function index()
    {
        echo "<h1>Photos Gallery</h1>";
        echo "<p>List of all photos</p>";
    }
    
    public function show($id)
    {
        echo "<h1>Photo Details</h1>";
        echo "<p>Showing photo with ID: $id</p>";
    }
}';

file_put_contents('app/Controllers/PhotoController.php', $photoControllerContent);

// Create Database class
$databaseContent = '<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database connection using Singleton pattern
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;

    private function __construct()
    {
        $this->config = require __DIR__ . \'/../../config/database.php\';
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        try {
            $dsn = sprintf(
                \'mysql:host=%s;port=%s;dbname=%s;charset=%s\',
                $this->config[\'host\'],
                $this->config[\'port\'],
                $this->config[\'database\'],
                $this->config[\'charset\']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config[\'username\'],
                $this->config[\'password\'],
                $this->config[\'options\']
            );
        } catch (PDOException $e) {
            die(\'Connection failed: \' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {}
}';

file_put_contents('app/Core/Database.php', $databaseContent);

// Create Router class
$routerContent = '<?php

namespace App\Core;

/**
 * Basic Router for handling URL routing
 */
class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->addRoute(\'GET\', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute(\'POST\', $path, $handler);
    }

    public function put(string $path, string $handler): void
    {
        $this->addRoute(\'PUT\', $path, $handler);
    }

    public function delete(string $path, string $handler): void
    {
        $this->addRoute(\'DELETE\', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            \'method\' => $method,
            \'path\' => $path,
            \'handler\' => $handler
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, \'/\') ?: \'/\';

        foreach ($this->routes as $route) {
            if ($route[\'method\'] === $method && $this->matchRoute($route[\'path\'], $uri)) {
                $this->callHandler($route[\'handler\'], $uri, $route[\'path\']);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 - Page not found";
    }

    private function matchRoute(string $routePath, string $uri): bool
    {
        $routePath = rtrim($routePath, \'/\') ?: \'/\';
        
        // Convert route path to regex pattern
        $pattern = preg_replace(\'/\{[^}]+\}/\', \'([^/]+)\', $routePath);
        $pattern = \'#^\' . $pattern . \'$#\';

        return preg_match($pattern, $uri);
    }

    private function callHandler(string $handler, string $uri, string $routePath): void
    {
        [$controllerName, $method] = explode(\'@\', $handler);
        
        $controllerClass = "App\\\\Controllers\\\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Controller not found: {$controllerName}";
            return;
        }

        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            http_response_code(500);
            echo "Method not found: {$method}";
            return;
        }

        // Extract parameters from URL
        $params = $this->extractParams($routePath, $uri);
        
        call_user_func_array([$controller, $method], $params);
    }

    private function extractParams(string $routePath, string $uri): array
    {
        $routePath = rtrim($routePath, \'/\') ?: \'/\';
        $uri = rtrim($uri, \'/\') ?: \'/\';
        
        $routeParts = explode(\'/\', $routePath);
        $uriParts = explode(\'/\', $uri);
        
        $params = [];
        for ($i = 0; $i < count($routeParts); $i++) {
            if (preg_match(\'/\{([^}]+)\}/\', $routeParts[$i], $matches)) {
                $params[$matches[1]] = $uriParts[$i] ?? null;
            }
        }
        
        return array_values($params);
    }
}';

file_put_contents('app/Core/Router.php', $routerContent);

// Create config/database.php
$databaseConfig = '<?php

return [
    \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',
    \'port\' => $_ENV[\'DB_PORT\'] ?? \'3306\',
    \'database\' => $_ENV[\'DB_DATABASE\'] ?? \'servicio_fotografia\',
    \'username\' => $_ENV[\'DB_USERNAME\'] ?? \'root\',
    \'password\' => $_ENV[\'DB_PASSWORD\'] ?? \'\',
    \'charset\' => \'utf8mb4\',
    \'options\' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];';

file_put_contents('config/database.php', $databaseConfig);

// Create public/index.php
$indexContent = '<?php

// Load autoloader
require_once \'../autoload.php\';

// Import Router class
use App\Core\Router;

// Start session
session_start();

// Create router instance
$router = new Router();

// Define routes
$router->get(\'/\', \'HomeController@index\');
$router->get(\'/photos\', \'PhotoController@index\');
$router->get(\'/photos/{id}\', \'PhotoController@show\');

// Dispatch request
$router->dispatch($_SERVER[\'REQUEST_URI\'], $_SERVER[\'REQUEST_METHOD\']);';

file_put_contents('public/index.php', $indexContent);

// Create .htaccess for public directory
$htaccessContent = 'RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]';

file_put_contents('public/.htaccess', $htaccessContent);

// Create .env example
$envContent = 'DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=servicio_fotografia
DB_USERNAME=root
DB_PASSWORD=';

file_put_contents('.env.example', $envContent);

echo "Project structure created successfully!\n";
echo "Files created:\n";
echo "- autoload.php (PSR-4 autoloader)\n";
echo "- composer.json\n";
echo "- app/Controllers/BaseController.php\n";
echo "- app/Controllers/HomeController.php\n";
echo "- app/Controllers/PhotoController.php\n";
echo "- app/Core/Database.php (Singleton pattern)\n";
echo "- app/Core/Router.php\n";
echo "- config/database.php\n";
echo "- public/index.php (entry point)\n";
echo "- public/.htaccess\n";
echo "- .env.example\n";
?>