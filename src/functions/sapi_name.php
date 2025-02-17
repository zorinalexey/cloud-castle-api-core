<?php

/**
 * @return string
 */
function sapi_name (): string
{
    return mb_strtoupper((PHP_SAPI === 'cli' ? PHP_SAPI : 'web'));
}