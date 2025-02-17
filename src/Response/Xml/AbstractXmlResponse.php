<?php

namespace CloudCastle\Core\Api\Response\Xml;

use CloudCastle\Core\Api\Response\AbstractResponse;
use CloudCastle\Core\Api\Response\ResponseInterface;
use XMLWriter;

abstract class AbstractXmlResponse extends AbstractResponse implements ResponseInterface
{
    private static XMLWriter|null $obj = null;
    
    public function __toString (): string
    {
        return $this->toXml();
    }
    
    private function toXml(): string
    {
        self::$obj = new XMLWriter();
        self::$obj->openMemory();
        self::$obj->startDocument('1.0', 'utf-8');
        
        $this->startElement('response');
            
        foreach($this as $name => $value) {
            $this->addElement($name, $value);
        }
            
        $this->closeElement();
        
        return self::$obj->outputMemory();
    }
    
    /**
     * Открыть элемент схемы
     * @param string $name Наименование элемента
     * @param array $attributes Атрибуты элемента
     * @param string|null $comment комментарий к элементу
     * @return void
     */
    private function startElement(string $name, array $attributes = [], ?string $comment = null): void
    {
        if ($comment) {
            self::$obj->startComment();
            self::$obj->text($comment);
            self::$obj->endComment();
        }
        
        self::$obj->startElement($name);
        
        if ($attributes) {
            foreach ($attributes AS $key => $value) {
                $this->addAttribute($key, $value);
            }
        }
    }
    
    /**
     * Закрыть элемент схемы
     * @return void
     */
    private function closeElement(): void
    {
        self::$obj->endElement();
    }
    
    /**
     * Добавить атрибут к элементу
     * @param string $name Наименование атрибута
     * @param string|int $text Значение атрибута
     * @return void
     */
    private function addAttribute(string $name, $text): void
    {
        if ($name AND $text) {
            self::$obj->startAttribute($name);
            self::$obj->text((string)$text);
            self::$obj->endAttribute();
        }
    }
    
    /**
     * Добавить элемент с содержанием
     * @param string $name Наименование элемента
     * @param mixed $content Содержание элемента
     * @return void
     */
    private function addElement(string $name, mixed $content): void
    {
        $this->startElement($name);
        
        if ($content !== null) {
            if(is_array($content) || is_object($content)) {
                foreach ($content AS $key => $value) {
                    $this->addElement($key, $value);
                }
            }else{
                self::$obj->text((string)$content);
            }
            
        }
        
        $this->closeElement();
    }
}