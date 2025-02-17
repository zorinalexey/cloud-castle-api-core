<?php

namespace CloudCastle\Core\Api\Common\Info;

/**
 *
 */
final readonly class File
{
    public string $basename;
    public string $dirname;
    public bool $exists;
    public string|false $content;
    public int|false $aTime;
    public int|false $cTime;
    public int|false $mTime;
    public int|false $group;
    public int|false $inode;
    public int|false $owner;
    public int|false $perms;
    public int|false $size;
    public string|false $type;
    public array|false $stat;
    public bool $isDir;
    public bool $isFile;
    public bool $isLink;
    public bool $isReadable;
    public bool $isExecutable;
    public bool $isUploaded;
    public bool $isWritable;
    public int|false $linkInfo;
    public string|array $pathInfo;
    public string|false $realPath;
    public string|false $contentType;
    public string $ext;
    
    public function __construct (string $path)
    {
        $this->basename = basename($path);
        $this->dirname = dirname($path);
        $this->exists = file_exists($path);
        $this->aTime = fileatime($path);
        $this->cTime = filectime($path);
        $this->mTime = filemtime($path);
        $this->group = filegroup($path);
        $this->inode = fileinode($path);
        $this->owner = fileowner($path);
        $this->perms = fileperms($path);
        $this->size = filesize($path);
        $this->type = filetype($path);
        $this->stat = $this->getStat($path);
        $this->isDir = is_dir($path);
        $this->isFile = is_file($path);
        $this->isLink = is_link($path);
        $this->isReadable = is_readable($path);
        $this->isExecutable = is_executable($path);
        $this->isUploaded = is_uploaded_file($path);
        $this->isWritable = is_writable($path);
        $this->linkInfo = linkinfo($path);
        $this->pathInfo = pathinfo($path);
        $this->realPath = realpath($path);
        $this->contentType = mime_content_type($path);
        
        if ($this->isFile) {
            $this->content = file_get_contents($path);
        } else {
            $this->content = '';
        }
        
        $this->ext = $this->getExt();
    }
    
    private function getStat (string $path): array
    {
        $data = [];
        
        foreach (stat($path) as $key => $value) {
            if (is_string($key)) {
                $data[$key] = $value;
            }
        }
        
        return $data;
    }
    
    private function getExt (): string|null
    {
        return match ($this->contentType) {
            'audio/aac' => 'aac',
            'application/x-abiword' => 'abw',
            'application/x-freearc' => 'arc',
            'video/x-msvideo' => 'avi',
            'application/vnd.amazon.ebook' => 'azw',
            'application/octet-stream' => 'bin',
            'image/bmp' => 'bmp',
            'application/x-bzip' => 'bz',
            'application/x-bzip2' => 'bz2',
            'application/x-csh' => 'csh',
            'text/css' => 'css',
            'text/csv' => 'csv',
            'application/msword' => 'doc',
            'pplication/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-fontobject' => 'eot',
            'application/epub+zip' => 'epub',
            'application/gzip' => 'gz',
            'image/gif' => 'gif',
            'text/html' => 'html',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'image/jpeg' => 'jpeg',
            'text/javascript' => 'js',
            'application/json' => 'json',
            'application/ld+json' => 'jsonld',
            'audio/midi' => 'mid',
            'audio/x-midi' => 'midi',
            'audio/mpeg' => 'mp3',
            'video/mpeg' => 'mpeg',
            'application/vnd.apple.installer+xml' => 'mpkg',
            'application/vnd.oasis.opendocument.presentation' => 'odp',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'audio/ogg' => 'oga',
            'video/ogg' => 'ogv',
            'application/ogg' => 'ogx',
            'audio/opus' => 'opus',
            'font/otf' => 'otf',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'application/php', 'text/php', 'text/x-php' => 'php',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/rtf' => 'rtf',
            'application/x-sh' => 'sh',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'image/tiff' => 'tiff',
            'video/mp2t' => 'ts',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'application/vnd.visio' => 'vsd',
            'audio/wav' => 'wav',
            'audio/webm' => 'weba',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/xml', 'application/xml' => 'xml',
            'application/vnd.mozilla.xul+xml' => 'xul',
            'application/zip' => 'zip',
            'video/3gpp', 'audio/3gpp' => '3gp',
            'video/3gpp2', 'audio/3gpp2' => '3g2',
            'application/x-7z-compressed' => '7z',
            default => null
        };
    }
}