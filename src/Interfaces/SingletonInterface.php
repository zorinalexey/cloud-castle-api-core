<?php

namespace CloudCastle\Core\Api\Interfaces;

interface SingletonInterface
{
    /**
     * @return self
     */
    public static function getInstance (): self;
}