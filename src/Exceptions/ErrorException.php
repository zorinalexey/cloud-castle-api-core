<?php

namespace CloudCastle\Core\Api\Exceptions;

final class ErrorException extends CoreException
{
    public function __construct (int $code, string $message, string $file, int $line)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}