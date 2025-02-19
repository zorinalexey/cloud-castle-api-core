<?php

namespace CloudCastle\Core\Api\Resources;

use stdClass;

/**
 * @property string $entity
 * @property string $prefix
 */
abstract class AbstractResource extends stdClass
{
    protected readonly string $prefixUrl;
    /**
     * @param array|object|null $data
     */
    protected function __construct (array|object|null $data = null)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $domain = $_SERVER['HTTP_HOST'];
        
        $this->prefixUrl = $protocol . $domain;
        
        foreach ($data ?? (object)[] as $key => $value) {
            if(!property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
    
    /**
     * @param array|null $data
     * @return array
     */
    final public static function collection (array|null $data = null): array
    {
        $collection = [];
        
        if ($data === null) {
            return $collection;
        }
        
        foreach ($data as $item) {
            $key = md5(json_encode($item));
            $collection[$key] = self::make($item);
        }
        
        return array_values($collection);
    }
    
    /**
     * @param array|object|null $data
     * @return stdClass
     */
    final public static function make (array|object|null $data = null): stdClass
    {
        $object = new static($data);
        $result = [];
        
        foreach ($object->toArray() as $name => $value) {
            $result[$name] = $value;
        }
        
        unset($object);
        
        return (object)$result;
    }
    
    /**
     * @return array
     */
    abstract public function toArray (): array;
    
    /**
     * @param string $name
     * @return mixed
     */
    public function __get (string $name): mixed
    {
        return $this->{$name} ?? null;
    }
}