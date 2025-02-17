<?php

namespace CloudCastle\Core\Api\Exceptions;

use Exception;
use Throwable;

class CoreException extends Exception
{
    public string|null $type = null;
    
    public function __construct (string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setType($code);
    }
    
    
    protected function setType (int $code): void
    {
        $this->type = match ($code) {
            E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => 'Error',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'Warning',
            E_NOTICE, E_USER_NOTICE => 'Notice',
            default => 'Exception',
        };
    }
}