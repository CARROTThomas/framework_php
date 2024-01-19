<?php

namespace Core\Router;

use Core\Http\Request;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes;

    /**
     * @throws \ReflectionException
     */
    public function __construct()
    {
        ///$this->routes = $this->getRoutes([\App\Controller\HomeController::class,]);
        $controllerDirectory = __DIR__ . '/../../src/Controller';

        $controllerFiles = scandir($controllerDirectory);
        $controllerFiles = array_filter($controllerFiles, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });

        $controllerClasses = array_map(function ($file) {
            return '\\App\\Controller\\' . pathinfo($file, PATHINFO_FILENAME);
        }, $controllerFiles);

        $this->routes = $this->getRoutes($controllerClasses);
    }


    public function getRoutes(array $controllers)
    {

        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);
            $methodsInController = $reflectionController->getMethods();

            $parentClass = $reflectionController->getParentClass();
            $methodsInAbstractController = $parentClass ? $parentClass->getMethods() : [];

            $methodsOnlyInControllerOnly = array_udiff(
                $methodsInController,
                $methodsInAbstractController,
                function ($a, $b) {
                    return strcmp($a->getName(), $b->getName());
                }
            );

            foreach ($methodsOnlyInControllerOnly as $method) {

                $attributes = $method->getAttributes(\Core\Attributes\Route::class);


                foreach ($attributes as $attribute) {


                    $argument = $attribute->getArguments();
                    $route = new Route();
                    $route->setUri($argument['uri']);
                    $route->setName($argument['name']);
                    $route->setMethods(array_map('strtoupper', $argument['methods']));
                    $route->setController($controller);
                    $route->setMethod($method->getName());
                    //$this->routes[] = $route; // faire le addRoute() à la place
                    $this->addRoute($route);
                }
                //$this->routes = $route;
            }

        }
        return $this->routes;
    }

    public function addRoute($route){
        $this->routes[] = $route;
        //$this->routes[$route['route']] = $route['c&m'];
    }







    public function getControllerAndMethod(Request $request)
    {
        $globals = $request->getGobals();
        $uri = $globals['REQUEST_URI'];

        return $this->getControllerAndMethodFromUri($uri);
    }

    private function getControllerAndMethodFromUri(string $uri)
    {
        foreach ($this->routes as $route) {
            if ($route->getUri() === $uri) {

                return $route;
            }
        }
    }
}