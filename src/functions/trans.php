<?php

use CloudCastle\Core\Api\Common\Lang\Lang;

/**
 * @param string $key
 * @param array $replace
 * @return string
 */
function trans (string $key, array $replace = []): string
{
    return Lang::getInstance()->translate($key, $replace);
}