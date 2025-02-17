<?php

namespace CloudCastle\Core\Api\Common\Lang;

use stdClass;

final class Lang extends stdClass
{
    /**
     * @var Lang|null
     */
    private static self|null $instance = null;
    
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
            if (is_file($file) && $key = basename($file, '.php')) {
                foreach (require_once $file as $i => $v) {
                    if (!isset(self::$instance->{$key})) {
                        self::$instance->{$key} = new stdClass();
                    }
                    
                    self::$instance->{$key}->{$i} = $v;
                }
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
     * @param mixed $value
     * @return void
     */
    public function __set (string $key, mixed $value): void
    {
        $this->{$key} = $value;
    }
    
    /**
     * @param string $key
     * @return mixed
     */
    public function get (string $key): mixed
    {
        $indexes = explode('.', $key);
        $check = false;
        $result = $key;
        
        foreach ($indexes as $index) {
            if (isset($this->{$index}) && is_object($this->{$index})) {
                $check = $this->{$index};
            }
            
            if ($check && is_object($check) && property_exists($check, $index)) {
                $result = $check->{$index};
            }
        }
        
        return $result;
    }
    
    /**
     * @param string $key
     * @param array $replace
     * @return string
     */
    public function translate (string $key, array $replace = []): string
    {
        return str_replace(array_keys($replace), array_values($replace), $this->get($key));
    }
}