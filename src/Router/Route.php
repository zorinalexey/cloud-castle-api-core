<?php

namespace CloudCastle\Core\Api\Router;

use CloudCastle\Core\Api\Router\Routes\AbstractRoute;
use CloudCastle\Core\Api\Router\Routes\Copy;
use CloudCastle\Core\Api\Router\Routes\Delete;
use CloudCastle\Core\Api\Router\Routes\Get;
use CloudCastle\Core\Api\Router\Routes\Link;
use CloudCastle\Core\Api\Router\Routes\Lock;
use CloudCastle\Core\Api\Router\Routes\Patch;
use CloudCastle\Core\Api\Router\Routes\Post;
use CloudCastle\Core\Api\Router\Routes\Put;
use CloudCastle\Core\Api\Router\Routes\UnLink;
use CloudCastle\Core\Api\Router\Routes\UnLock;

final class Route
{
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function get (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Get($path, $controller, $action, 'GET'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function post (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Post($path, $controller, $action, 'POST'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function put (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Put($path, $controller, $action, 'PUT'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function patch (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Patch($path, $controller, $action, 'PATCH'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function copy (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Copy($path, $controller, $action, 'COPY'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function link (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Link($path, $controller, $action, 'LINK'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function unlink (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new UnLink($path, $controller, $action, 'UNLINK'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function lock (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Lock($path, $controller, $action, 'LOCK'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function unlock (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new UnLock($path, $controller, $action, 'UNLOCK'));
    }
    
    /**
     * @param string $path
     * @param string $controller
     * @param string $action
     * @return AbstractRoute
     */
    public static function delete (string $path, string $controller, string $action = '__invoke'): AbstractRoute
    {
        return Router::getInstance()->addRoute(new Delete($path, $controller, $action, 'DELETE'));
    }
}