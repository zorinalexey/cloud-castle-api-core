<?php

use CloudCastle\Core\Api\Common\Config\Config;

/**
 * @param string|null $key
 * @param mixed|null $default
 * @return mixed
 */
function config (string|null $key = null, mixed $default = null): mixed
{
    $config = Config::getInstance();
    
    if (!$config) {
        return $default;
    }
    
    if (!$key) {
        return $config;
    }
    
    return $config->get($key, $default);
}