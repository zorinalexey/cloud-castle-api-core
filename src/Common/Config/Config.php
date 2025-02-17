<?php

namespace CloudCastle\Core\Api\Common\Config;

use CloudCastle\Core\Api\Interfaces\SingletonInterface;
use stdClass;

final class Config extends stdClass implements SingletonInterface
{
    /**
     * @var Config|null
     */
    private static self|null $instance = null;
    
    /**
     *
     */
    private function __construct ()
    {
    }
    
    /**
     * @return self|null
     */
    public static function getInstance (): self|null
    {
        return self::$instance;
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
        
        $files = scan_dir($path, ext : ['.php']);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $key = str_replace('.php', '', basename($file));
                self::$instance->{$key} = (object) [...self::$instance->{$key} ?? [], ...require_once $file];
            }
        }
        
        return self::$instance;
    }
    
    /**
     * @param string $key
     * @return mixed
     */
    public function __get (string $key): mixed
    {
        return $this->get($key);
    }
    
    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get (string $key, mixed $default = null): mixed
    {
        return $this->{$key} ?? $default;
    }
}