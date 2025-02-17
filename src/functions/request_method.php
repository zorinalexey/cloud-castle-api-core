<?php

/**
 * @return string
 */
function request_method (): string
{
    return mb_strtoupper($_SERVER['REQUEST_METHOD'] ?? sapi_name());
}