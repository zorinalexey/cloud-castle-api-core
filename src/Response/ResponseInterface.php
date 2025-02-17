<?php

namespace CloudCastle\Core\Api\Response;

interface ResponseInterface
{
    public function __toString(): string;
}