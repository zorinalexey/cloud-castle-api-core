<?php

/**
 * @return string
 */
function content_type (): string
{
    $headers = headers();
    
    return $headers['Content-Type'] ?? 'text/html';
}