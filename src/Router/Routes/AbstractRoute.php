<?php

namespace CloudCastle\Core\Api\Router\Routes;

use CloudCastle\Core\Api\Middleware\AbstractMiddleware;
use CloudCastle\Core\Api\Middleware\MiddlewareException;
use CloudCastle\Core\Api\Request\FormRequest;
use CloudCastle\Core\Api\Request\Request;
use CloudCastle\Core\Api\Router\RouteException;
use ReflectionException;
use stdClass;

abstract class AbstractRoute extends stdClass
{
    /**
     * @var string
     */
    public string $pattern;
    
    /**
     * @var string
     */
    public readonly string $permissionKey;
    
    /**
     * @var string
     */
    private string $path = '/';
    
    /**
     * @var string[]
     */
    private readonly array $methods;
    
    /**
     * @var string
     */
    private readonly string $controller;
    
    /**
     * @var string
     */
    private readonly string $action;
    
    /**
     * @var array
     */
    private array $args = [];
    
    /**
     * @var array
     */
    private array $middlewares = [];
    
    /**
     * @var string|null
     */
    private string|null $name = null;
    
    /**
     * @var array
     */
    private array $middlewareErrors = [];
    
    /**
     * @var int
     */
    private int $httpCode = 200;
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @param string $method
     */
    
    public function __construct (string $path, string $controller, string $action, string $method)
    {
        $this->path = '/' . trim($path, '/');
        $this->controller = $controller;
        $this->action = $action;
        $this->methods = [$method, 'OPTIONS'];
        $this->setPattern();
        $this->setPermissionKey();
        $this->setName();
    }
    
    /**
     * @return void
     */
    private function setPattern (): void
    {
        $pattern = '([\w.-]+)';
        $this->pattern = '~^(' . str_replace('.', '\\.', $this->path) . ')$~ui';
        $request = Request::getInstance();
        
        if ($this->path && preg_match_all('~{' . $pattern . '}~ui', $this->path, $matches)) {
            foreach ($matches[1] as $key => $match) {
                $this->pattern = str_replace($matches[0][$key], "(?<{$match}>[\w.-]+)", $this->pattern);
                
                if(preg_match($this->pattern, $request->request_uri, $reqMatchesh)) {
                    foreach ($reqMatchesh as $key => $reqMatch) {
                        if(is_string($key)) {
                            $this->{$match} = $reqMatch;
                            $request->{$key} = $reqMatch;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @return void
     */
    private function setPermissionKey (): void
    {
        $this->permissionKey = mb_strtolower($this->methods[0]) . str_replace(['{', '}', '/'], ['', '', '.'], $this->path);
    }
    
    /**
     * @return void
     */
    private function setName (): void
    {
        if (!$this->name) {
            $this->name = $this->permissionKey;
        }
    }
    
    /**
     * @return string
     */
    public function getPath (): string
    {
        return $this->path;
    }
    
    /**
     * @return string[]
     */
    public function getMethods (): array
    {
        return $this->methods;
    }
    
    /**
     * @return string
     */
    public function getController (): string
    {
        return $this->controller;
    }
    
    /**
     * @return string
     */
    public function getAction (): string
    {
        return $this->action;
    }
    
    /**
     * @param string $name
     * @return $this
     */
    public function name (string $name): self
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * @param array $args
     * @return $this
     */
    public function args (array $args): self
    {
        $this->args = $args;
        
        return $this;
    }
    
    /**
     * @param array $middlewares
     * @return $this
     * @throws MiddlewareException
     * @throws ReflectionException
     */
    public function middleware (array $middlewares): self
    {
        $implClass = AbstractMiddleware::class;
        
        foreach ($middlewares as $middleware) {
            $classes = getClassImplements($middleware);
            
            if (in_array($implClass, $classes)) {
                $this->middlewares[$middleware] = $middleware;
            } else {
                throw new MiddlewareException(
                    trans("router.Class ':middleware' is not implemented ':implClass'", [':middleware' => $middleware, ':implClass' => $implClass]),
                    1005
                );
            }
        }
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getName (): string
    {
        return $this->name;
    }
    
    /**
     * @return mixed
     * @throws ReflectionException
     * @throws RouteException
     */
    public function run (): mixed
    {
        $this->runChecks();
        
        if (!$this->middlewareErrors) {
            $args = $this->getArgs();
            
            return (new ($this->controller))->{($this->action)}(...$args);
        }
        
        throw new RouteException(implode(PHP_EOL, $this->middlewareErrors), $this->httpCode);
    }
    
    /**
     * @return void
     * @throws RouteException
     */
    private function runChecks (): void
    {
        if (!class_exists($this->controller)) {
            throw new RouteException(
                trans("router.Controller ':controller' does not exist", [':controller' => $this->controller]),
                500
            );
        }
        
        if (!method_exists($this->controller, $this->action)) {
            throw new RouteException(
                trans("router.Action :action does not exist in controller :controller", [':controller' => $this->controller, ':action' => $this->action]),
                500
            );
        }
        
        if ($this->middlewares) {
            /** @var AbstractMiddleware $middleware */
            foreach ($this->getMiddlewares() as $middleware) {
                if (!(new $middleware())->run(Request::getInstance())) {
                    $this->middlewareErrors[] = $middleware->getMessage();
                    $this->httpCode = $middleware->getCode();
                }
            }
        }
    }
    
    /**
     * @return array
     */
    public function getMiddlewares (): array
    {
        return array_values($this->middlewares);
    }
    
    /**
     * @return array
     * @throws ReflectionException
     */
    public function getArgs (): array
    {
        $args = [];
        
        foreach ($this->args as $key => $arg) {
            if (property_exists($this, $arg) && !is_null($arg)) {
                $args[$key] = $this->{$arg};
            }
            
            if (class_exists($arg) && in_array(FormRequest::class, getClassImplements($arg))) {
                $args[$key] = new $arg();
            }
        }
        
        return $args;
    }
}