<?php

namespace CloudCastle\Core\Api\Resources;

use stdClass;

/**
 * @property string $entity
 * @property string $prefix
 */
abstract class AbstractResource extends stdClass
{
    /**
     * @var array|object|null
     */
    private array|null|object $data = null;
    
    /**
     * @param array|object|null $data
     */
    final protected function __construct (array|object|null $data = null)
    {
        $this->data = $data;
        
        foreach ($this->data ?? [] as $key => $value) {
            $this->{$key} = $value;
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
        
        foreach ($data as $object) {
            $collection[] = self::make($object);
        }
        
        return array_values($collection);
    }
    
    /**
     * @param array|object|null $data
     * @return array
     */
    final public static function make (array|object|null $data = null): array
    {
        $object = new static($data);
        
        foreach ($data ?? [] as $key => $value) {
            $object->{$key} = $value;
        }
        
        $result = [];
        
        foreach ($object->toArray() as $name => $value) {
            $result[$name] = $value;
        }
        
        unset($object);
        
        return $result;
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
        if ($this->data === null) {
            return null;
        }
        
        if (is_object($this->data)) {
            return $this->data->{$name} ?? null;
        }
        
        return $this->data[$name] ?? null;
    }
}