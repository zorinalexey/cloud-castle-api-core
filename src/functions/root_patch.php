<?php

/**
 * @return string
 */
function root_patch (): string
{
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', __DIR__);
    }
    
    return ROOT_PATH;
}