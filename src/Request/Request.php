<?php

namespace CloudCastle\Core\Api\Request;

use CloudCastle\Core\Api\Interfaces\SingletonInterface;
use Exception;
use SimpleXMLElement;
use stdClass;

/**
 *
 */
final class Request extends stdClass implements SingletonInterface
{
    /**
     * @var Request|null
     */
    private static self|null $instance = null;
    
    /**
     * @var string
     */
    public readonly string $request_uri;
    
    /**
     * @var string
     */
    public string $trashed = 'not_trashed';
    
    /**
     *
     */
    private function __construct ()
    {
        foreach ($this->getRequestBody() as $key => $value) {
            $this->{$key} = $value;
        }
        
        $this->request_uri = request_uri();
    }
    
    /**
     * @return array
     */
    private function getRequestBody (): array
    {
        $input = $this->getInputData();
        $method = request_method();
        $addParams = [
            'headers' => (object) headers(),
            'server' => (object) ($_SERVER ?? []),
            'cookies' => (object) ($_COOKIE ?? []),
            'env' => $_ENV ?? [],
        ];
        
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $addParams['files'] = $this->getFiles();
        }
        
        return match (request_method()) {
            'POST' => [...$_GET, ...$input, ...$_POST, ...$addParams],
            'PATCH', 'PUT', 'DELETE' => [...$_GET, ...$_POST, ...$input, ...$addParams],
            'COPY', 'LINK', 'UNLINK', 'LOCK', 'UNLOCK' => [...$_GET, ...$input, ...$addParams],
            default => [...$input, ...$_GET, ...$addParams]
        };
    }
    
    /**
     * @return array
     */
    private function getInputData (): array
    {
        $contentType = content_type();
        $content = file_get_contents('php://input');
        $data = false;
        
        if ($content && str_contains($contentType, 'application/json')) {
            $data = $content;
        }
        
        if ($content && str_contains($contentType, 'application/xml')) {
            try {
                $data = json_encode(new SimpleXMLElement($content));
            } catch (Exception $e) {
            
            }
        }
        
        if ($data) {
            return json_decode($data, true) ?? [];
        }
        
        return [];
    }
    
    /**
     * @return array
     */
    private function getFiles (): array
    {
        $data = [];
        
        foreach ($_FILES as $key => $fileInfo) {
            if (is_array($fileInfo['name'])) {
                $totalFiles = count($fileInfo['name']);
                
                for ($i = 0; $i < $totalFiles; $i++) {
                    $data[$key][$i] = new UploadFile([
                        'name' => $fileInfo['name'][$i],
                        'type' => $fileInfo['type'][$i],
                        'tmp_name' => $fileInfo['tmp_name'][$i],
                        'error' => $fileInfo['error'][$i],
                        'size' => $fileInfo['size'][$i]
                    ]);
                }
            } else {
                $data[$key] = new UploadFile($fileInfo);
            }
        }
        
        return $data;
    }
    
    /**
     * @return self
     */
    public static function getInstance (): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * @param string $name
     * @return mixed
     */
    public function __get (string $name): mixed
    {
        return $this->{$name} ?? null;
    }
    
    /**
     * @param string $name
     * @param $value
     * @return void
     */
    public function __set (string $name, $value): void
    {
        $this->{$name} = $value;
    }
    
    /**
     * @return void
     */
    private function __clone (): void
    {
    
    }
}