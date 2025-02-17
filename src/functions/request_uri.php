<?php

/**
 * @return string
 */
function request_uri (): string
{
    $requestUri = $_SERVER['REQUEST_URI'];
    $questionMarkPosition = strpos($requestUri, '?');
    
    if ($questionMarkPosition !== false) {
        $pathOnly = substr($requestUri, 0, $questionMarkPosition);
    } else {
        $pathOnly = $requestUri;
    }
    
    return $pathOnly;
}