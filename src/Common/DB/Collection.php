<?php

namespace CloudCastle\Core\Api\Common\DB;

use stdClass;

/**
 * @var mixed $collection
 * @var mixed $paginate
 */
final class Collection extends stdClass
{
    public mixed $collection = [];
    public mixed $paginate = [];
    
    public static function make(array|object $data): self
    {
        $obj = new self();
        
        foreach ($data as $key => $value) {
            $obj->{$key} = $value;
        }
        
        return $obj;
    }
    
    public function lenght(string $name = 'collection'): int
    {
        if(isset($this->{$name}) && $this->{$name}) {
            if((is_array($this->{$name}) || is_object($this->{$name}))){
                return count((array)$this->{$name});
            }else{
                return mb_strlen((string)$this->{$name});
            }
        }
        
        return 0;
    }
    
    public function __get(string $name): mixed
    {
        if(property_exists($this, $name)) {
            return $this->{$name};
        }
        
        return null;
    }
    
    public function __set(string $name, mixed $value): void
    {
        $this->{$name} = $value;
    }
}