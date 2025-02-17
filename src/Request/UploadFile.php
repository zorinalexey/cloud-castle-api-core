<?php

namespace CloudCastle\Core\Api\Request;

use CloudCastle\Core\Api\Common\Info\File;

/**
 *
 */
final readonly class UploadFile
{
    /**
     * @var string|mixed|null
     */
    public string|null $tmp_name;
    
    /**
     * @var string|mixed|null
     */
    public string|null $name;
    
    /**
     * @var string|mixed|null
     */
    public string|null $size;
    
    /**
     * @var string|mixed|null
     */
    public string|null $type;
    
    /**
     * @var string|mixed|null
     */
    public string|null $error;
    
    /**
     * @var File
     */
    public File $info;
    
    /**
     * @param array $file
     */
    public function __construct (array $file)
    {
        $this->tmp_name = $file['tmp_name'] ?? null;
        $this->name = $file['name'] ?? null;
        $this->size = $file['size'] ?? null;
        $this->type = $file['type'] ?? null;
        $this->error = $file['error'] ?? null;
        $this->info = new File($file['tmp_name']);
    }
    
    /**
     * @param string $dir
     * @param string|null $name
     * @return string|null
     */
    public function save (string $dir, string|null $name = null): ?string
    {
        if ($name === null) {
            $name = microtime(true) . $this->info->ext ? '.' . $this->info->ext : null;
        }
        
        $file = realpath($dir) . DIRECTORY_SEPARATOR . trim($name, DIRECTORY_SEPARATOR);
        
        if (move_uploaded_file($this->tmp_name, $file)) {
            return $file;
        }
        
        return null;
    }
}