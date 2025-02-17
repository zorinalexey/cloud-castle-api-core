<?php

/**
 * @return array
 */
function headers (): array
{
    $headers = [];
    
    foreach ($_SERVER as $key => $item) {
        if (preg_match('~HTTP~ui', $key)) {
            $keys = explode('_', $key);
            $name = '';
            
            foreach ($keys as $value) {
                $value = mb_strtolower($value);
                
                if (!str_contains('http', $value)) {
                    $name .= mb_ucfirst($value) . '-';
                }
            }
            
            $headers[trim($name, '-')] = $item;
        }
    }
    
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    }
    
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }
    
    return $headers;
}

/**
 * @param string $key
 * @param string|null $prefix
 * @return string|null
 */
function getHeader (string $key, string|null $prefix = null): string|null
{
    $key = strtolower(trim($key));
    
    foreach (headers() as $name => $value) {
        $name = strtolower(trim($name));
        
        if ($name === $key) {
            if ($prefix) {
                return trim(str_replace($prefix, '', $value));
            }
            
            return $value;
        }
    }
    
    return null;
}