<?php
// --- Simple MVC Framework in a Single File ---

// --- Router ---
class Router {
    private $routes = [];

    public function add($method, $pattern, $controller, $action) {
        $this->routes[] = compact('method', 'pattern', 'controller', 'action');
    }

    public function dispatch($method, $uri) {
        foreach ($this->routes as $route) {
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            if ($method === $route['method'] && preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$route['controller'], $route['action'], $params];
            }
        }
        return [null, null, []];
    }
}

// --- Base Controller ---
class Controller {
    protected function render($template, $vars = []) {
        echo Template::render($template, $vars);
    }
}

// --- Template Engine ---
class Template {
    public static function render($template, $vars = []) {
        // Load template file
        $tplFile = __DIR__ . "/templates/$template.php";
        if (!file_exists($tplFile)) return "Template not found: $template";
        $tpl = file_get_contents($tplFile);

        // Interpolate variables: {{ $var }} and expressions: {{ ... }}
        $tpl = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($m) use ($vars) {
            extract($vars);
            // Evaluate as PHP expression
            ob_start();
            eval('echo ' . $m[1] . ';');
            return ob_get_clean();
        }, $tpl);

        return $tpl;
    }
}

// --- Example Controllers ---
class HomeController extends Controller {
    public function index($params) {
        $name = $params['name'] ?? 'World';
        $this->render('home', ['name' => $name]);
    }
}

class AboutController extends Controller {
    public function info($params) {
        $this->render('about', ['year' => date('Y')]);
    }
}

// --- Front Controller ---
$router = new Router();
$router->add('GET', '/', 'HomeController', 'index');
$router->add('GET', '/about', 'AboutController', 'info');
$router->add('GET', '/hello/{name}', 'HomeController', 'index');

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
list($controller, $action, $params) = $router->dispatch($method, $uri);

if ($controller && $action) {
    if (!class_exists($controller)) die("Controller not found");
    $ctrl = new $controller();
    if (!method_exists($ctrl, $action)) die("Action not found");
    $ctrl->$action($params);
} else {
    http_response_code(404);
    echo "404 Not Found";
}

// --- Example Templates ---
// Create templates/home.php and templates/about.php in the same directory:

/*
templates/home.php:
-------------------
<h1>Hello, {{ \$name }}!</h1>
<p>Welcome to our MVC demo.</p>

templates/about.php:
--------------------
<h1>About</h1>
<p>The year is {{ \$year }}.</p>
*/