<?php

/**
 * @return string
 */
function host (): string
{
    return $_SERVER['HTTP_HOST'] ?? sapi_name();
}