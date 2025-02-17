<?php

namespace CloudCastle\Core\Api\Middleware;

use CloudCastle\Core\Api\Request\Request;
use stdClass;

abstract class AbstractMiddleware extends stdClass
{
    /**
     * @var string|null
     */
    protected string|null $message = null;
    
    /**
     * @var int
     */
    protected int $code = 200;
    
    /**
     * @param Request $request
     * @return mixed
     */
    abstract public function run (Request $request): mixed;
    
    /**
     * @return string
     */
    public function getMessage (): string
    {
        return trans("middleware.{$this->message}");
    }
    
    /**
     * @return int
     */
    public function getCode (): int
    {
        return $this->code;
    }
}