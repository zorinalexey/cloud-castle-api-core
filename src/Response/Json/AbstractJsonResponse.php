<?php

namespace CloudCastle\Core\Api\Response\Json;

use CloudCastle\Core\Api\Response\AbstractResponse;
use CloudCastle\Core\Api\Response\ResponseInterface;
use JsonException;

abstract class AbstractJsonResponse extends AbstractResponse implements ResponseInterface
{
    /**
     * @throws JsonException
     */
    public function __toString (): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}