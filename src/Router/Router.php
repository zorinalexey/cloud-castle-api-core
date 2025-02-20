<?php

namespace CloudCastle\Core\Api\Router;

use CloudCastle\Core\Api\Interfaces\SingletonInterface;
use CloudCastle\Core\Api\Request\Request;
use CloudCastle\Core\Api\Response\ResponseInterface;
use CloudCastle\Core\Api\Router\Routes\AbstractRoute;

final class Router implements SingletonInterface
{
    /**
     * @var Router|null
     */
    private static self|null $instance = null;
    
    /**
     * @var array
     */
    private array $routeList = [];
    
    /**
     * @var Request
     */
    private Request $request;
    
    private static AbstractRoute|null $currentRoute = null;
    
    /**
     *
     */
    private function __construct ()
    {
        $this->request = Request::getInstance();
    }
    
    /**
     * @return self
     */
    public static function getInstance (): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * @param AbstractRoute $route
     * @return AbstractRoute
     */
    public static function addRoute (AbstractRoute $route): AbstractRoute
    {
        $id = $route->permissionKey;
        self::$instance->routeList[$id] = $route;
        
        return self::$instance->routeList[$id];
    }
    
    /**
     * @return array
     */
    public static function getRoutes (): array
    {
        return self::getInstance()->routeList;
    }
    
    /**
     * @return mixed
     * @throws RouteException
     */
    public static function run (): ResponseInterface
    {
        $router = self::getInstance();
        $request_uri = $router->request->request_uri;
        $routeFiles = scan_dir(root_patch() . DIRECTORY_SEPARATOR . 'routes', ext : ['.php']);
        
        foreach ($routeFiles as $file) {
            require_once $file;
        }
        
        foreach ($router->routeList as $route) {
            /** @var AbstractRoute $route */
            if (preg_match($route->pattern, $request_uri, $matches) && in_array(request_method(), $route->getMethods())) {
                self::$currentRoute = $route;
                
                 return $route->run();
            }
        }
        
        throw new RouteException(trans('router.404 - Page not found!!!'), 404);
    }
    
    public static function getCurrentRoute (): AbstractRoute
    {
        return self::$currentRoute;
    }
    
    public static function getRoute (string $permissionKey): AbstractRoute|null
    {
        return self::$instance->routeList[$permissionKey]?? null;
    }
    
    /**
     * @return void
     */
    private function __clone ()
    {
    
    }
}