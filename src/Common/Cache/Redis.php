<?php

namespace CloudCastle\Core\Api\Common\Cache;

use CloudCastle\Core\Api\Interfaces\SingletonInterface;

final class Redis  implements SingletonInterface
{
    
    public static function getInstance (): SingletonInterface
    {
        if(!extension_loaded('redis')) {
            throw new \BadMethodCallException('Redis extension is not loaded');
        }
    }
}