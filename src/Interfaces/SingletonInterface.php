<?php

namespace CloudCastle\Core\Api\Interfaces;

interface SingletonInterface
{
    /**
     * @return mixed
     */
    public static function getInstance (): mixed;
}