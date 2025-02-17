<?php

use CloudCastle\Core\Api\Request\Request;

/**
 * @return Request
 */
function request (): Request
{
    return Request::getInstance();
}