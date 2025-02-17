<?php

use CloudCastle\Core\Api\Common\Config\Env;

/**
 * @param string|null $key
 * @param $default
 * @return mixed
 */
function env (string|null $key = null, $default = null): mixed
{
    $env = Env::getInstance();
    
    if (!$env) {
        return $default;
    }
    
    if (!$key) {
        return $env;
    }
    
    return $env->get($key, $default);
}