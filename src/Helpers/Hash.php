<?php

namespace CloudCastle\Core\Api\Helpers;

final class Hash
{
    /**
     * @param string $string
     * @param string|null $alg
     * @return string
     */
    public static function make (string $string, string|null &$alg = 'password_hash'): string
    {
        if (!in_array($alg, hash_hmac_algos()) || $alg === 'password_hash') {
            $alg = 'password_hash';
        }
        
        return match ($alg) {
            'password_hash' => password_hash($string, PASSWORD_DEFAULT),
            default => hash($alg, $string),
        };
    }
}