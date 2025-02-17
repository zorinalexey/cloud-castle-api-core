<?php

namespace CloudCastle\Core\Api\Response;


abstract class AbstractResponse implements ResponseInterface
{
    
    public bool $success = true;
    
    public bool $error = false;
    
    public int $code = 200;
    
    public string|null $message = null;
    
    public array $errors = [];
    
    public array $data = [];
    
    abstract public function __toString(): string;
    
    public function __construct(array $data, array $errors = [], string|null $message = null, int $code = 200, array $options = [])
    {
        $this->data = $data;
        $this->errors = $errors;
        $this->message = $message;
        $this->code = $code;
        
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
    }
}