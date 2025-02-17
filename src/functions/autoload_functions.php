<?php

use CloudCastle\Core\Api\Common\Config\Config;
use CloudCastle\Core\Api\Common\Lang\Lang;
use CloudCastle\Core\Api\Common\Log\Log;

define('CORE_PATH', dirname(__DIR__, 2));

/**
 * @param string $dir
 * @param bool $recursive
 * @param array $ignoreList
 * @param array $ext
 * @return array
 */
function scan_dir (string $dir, bool $recursive = true, array $ignoreList = [__FILE__], array $ext = []): array
{
    if (!is_dir($dir)) return [];
    
    $data = [];
    $dir = realpath($dir);
    
    $includeFile = function (string $filePatch) use (&$data, $ext){
        $fileExt = preg_replace('~^(.+)?\.(\w+)$~ui', '.$2', $filePatch);
        
        if ($ext) {
            if (in_array($fileExt, $ext)) {
                $data[] = $filePatch;
            }
        } else {
            $data[] = $filePatch;
        }
    };
    
    foreach (scandir($dir) as $item) {
        $filePatch = realpath($dir . DIRECTORY_SEPARATOR . $item);
        
        if (is_dir($filePatch) && $recursive && !in_array($item, ['.', '..'])) {
            $data = [...$data, ...scan_dir($filePatch, $recursive, $ignoreList, $ext)];
        }
        
        if (is_file($filePatch)) {
            if ($ignoreList) {
                $include = true;
                
                foreach ($ignoreList as $ignore) {
                    if (preg_match('~(.+)?(' . $ignore . ')(.+)?~ui', $filePatch)) {
                        $include = false;
                    }
                }
                
                if ($include) {
                    $includeFile($filePatch);
                }
            } else {
                $includeFile($filePatch);
            }
        }
    }
    
    return $data;
}

foreach (scan_dir(dir : __DIR__, ext : ['.php']) as $file) {
    require_once $file;
}
