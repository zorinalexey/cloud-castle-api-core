<?php

namespace CloudCastle\Core\Api\Response\Json;

use CloudCastle\Core\Api\Response\AbstractResponse;
use CloudCastle\Core\Api\Response\ResponseInterface;
use JsonException;

abstract class AbstractJsonResponse extends AbstractResponse implements ResponseInterface
{
    private static string $contentType = 'application/json';

    /**
     * @throws JsonException
     */
    public function __toString (): string
    {
        header(sprintf('Content-type: %s', self::$contentType));
        
        if(!$this->errors){
            $this->errors = (object)[];
        }
        
        return json_encode($this, JSON_THROW_ON_ERROR);
    }
}