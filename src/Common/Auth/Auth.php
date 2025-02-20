<?php

namespace CloudCastle\Core\Api\Common\Auth;

final class Auth
{
    private static self|null $instance = null;
    
    public static function login(array|object $data): self
    {
        $instance = self::getInstance();
        
        foreach ($data as $key => $value) {
            $instance->{$key} = $value;
        }
        
        return $instance;
    }
    
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public static function user(): self
    {
        return self::getInstance();
    }
    
    public function __get(string $name):mixed
    {
        if(!method_exists($this, $name)) {
            return null;
        }
        
        return $this->{$name}();
    }
}