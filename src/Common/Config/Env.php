<?php

namespace CloudCastle\Core\Api\Common\Config;

use CloudCastle\Core\Api\Interfaces\SingletonInterface;
use stdClass;

final class Env extends stdClass implements SingletonInterface
{
    /**
     * @var Env|null
     */
    private static self|null $instance = null;
    
    /**
     *
     */
    private function __construct ()
    {
    }
    
    /**
     * @param string $path
     * @return self
     */
    public static function init (string $path): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    $_ENV[$key] = $value;
                    putenv(sprintf('%s=%s', $key, $value));
                    self::$instance->{$key} = $value;
                }
            }
        }
        
        return self::getInstance();
    }
    
    /**
     * @return self|null
     */
    public static function getInstance (): self|null
    {
        return self::$instance;
    }
    
    /**
     * @param string $env
     * @return mixed
     */
    public function __get (string $env): mixed
    {
        return $this->get($env);
    }
    
    /**
     * @param string $env
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get (string $env, mixed $default = null)
    {
        $property = mb_strtoupper($env);
        
        return $this->{$property} ?? $default;
    }
}