<?php

namespace CloudCastle\Core\Api\Response;

use stdClass;

abstract class AbstractResponse extends stdClass implements ResponseInterface
{
    public mixed $data = [];
    public bool $success = true;
    
    public bool $error = false;
    
    public int $code = 200;
    
    public string|null $message = null;
    
    public object|null $errors = null;
    
    abstract public function __toString(): string;
    
    public function __construct(array|object $data, array $errors = [], string|null $message = null, int $code = 200)
    {
        $this->errors = (object)$errors;
        $this->message = $message?(string)$message:trans('response.Ok');
        $this->code = (int)$code;
        
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}