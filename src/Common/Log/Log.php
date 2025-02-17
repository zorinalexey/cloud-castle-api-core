<?php

namespace CloudCastle\Core\Api\Common\Log;

use DateTime;
use Throwable;

final class Log
{
    /**
     * @var Log|null
     */
    private static self|null $instance = null;
    
    /**
     * @var DateTime
     */
    private DateTime $date;
    
    /**
     * @var string|null
     */
    private string|null $path = null;
    
    /**
     * @var string|null
     */
    private string|null $filename = null;
    
    /**
     * @var string|null
     */
    private string|null $level = 'Error';
    
    /**
     *
     */
    private function __construct ()
    {
        $this->date = new DateTime();
    }
    
    /**
     * @param array $config
     * @return self
     */
    public static function config (array $config): self
    {
        $obj = self::getInstance();
        
        foreach ($config as $key => $value) {
            
            if (property_exists($obj, $key)) {
                $obj->{$key} = $value;
            }
        }
        
        return $obj;
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
     * @param Throwable $exception
     * @return void
     */
    public static function write (Throwable $exception, string|null $filename = null): void
    {
        $obj = self::getInstance();
     
        if($filename) {
            $obj->filename = $filename;
        }
        
        if (property_exists($exception, 'type')) {
            $obj->level = $exception->type;
        }
        
        $context = $obj->getLogMessage($exception);
        file_put_contents($obj->getLogFile(), $context, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * @param Throwable $exception
     * @return string
     */
    private function getLogMessage (Throwable $exception): string
    {
        $separator = '=';
        $head = $this->getHeadLog($this, $exception, $separator);
        $str = $head;
        $str .= "Level: {$this->level}" . PHP_EOL;
        $str .= "Message: {$exception->getMessage()}" . PHP_EOL;
        $str .= "File: {$exception->getFile()}" . PHP_EOL;
        $str .= "Line: {$exception->getLine()}" . PHP_EOL;
        $str .= "Code: {$exception->getCode()}" . PHP_EOL;
        $str .= "Detail: " . $this->getDetail($exception) . PHP_EOL;
        $str .= "Trace: {$exception->getTraceAsString()}" . PHP_EOL;
        $this->setEndLogStr($str, $separator, $head);
        
        return $str;
    }
    
    /**
     * @param Log $obj
     * @param mixed $data
     * @param string $separator
     * @return string
     */
    private function getHeadLog (Log $obj, mixed $data, string $separator): string
    {
        $startQuote = '<<<<';
        $endQuote = '>>>>';
        $dataType = gettype($data);
        
        if (is_object($data)) {
            $dataType = get_class($data);
        }
        
        $startText = "    " . $obj->date->format('Y-m-d H:i:s') . " - {$dataType}    ";
        $length = mb_strlen(trim($startText));
        $iCount = ceil((120 - $length) / 2);
        $headStr = str_repeat($separator, $iCount);
        
        if (!is_file($obj->getLogFile())) {
            $head = $startQuote . '----' . $headStr;
        } else {
            $head = PHP_EOL . PHP_EOL . $startQuote . '----' . $headStr;
        }
        
        $head .= $startText;
        $head .= $headStr . '----' . $endQuote . PHP_EOL;
        $str = $head;
        
        return $str;
    }
    
    /**
     * @return string
     */
    private function getLogFile (): string
    {
        $this->setLogFile();
        $file = realpath($this->path) . DIRECTORY_SEPARATOR . $this->date->format('Y-m-d') . DIRECTORY_SEPARATOR . $this->filename;
        $logDir = dirname($file);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        return $file;
    }
    
    /**
     * @return void
     */
    private function setLogFile (): void
    {
        if (!$this->filename) {
            $this->filename = "{$this->level}.log";
        }
        
        if (!$this->path) {
            $this->path = __DIR__ . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . $this->date->format('Y-m-d');
        }
    }
    
    /**
     * @param Throwable $exception
     * @return string
     */
    private function getDetail (Throwable $exception): string
    {
        $str = PHP_EOL;
        
        foreach ($exception->getTrace() as $key => $trace) {
            $str .= "{$key} => [";
            
            foreach ($trace as $i => $detail) {
                $str .= PHP_EOL . "\t\t" . mb_ucfirst($i) . ": {$detail}";
            }
            
            $str .= PHP_EOL . "\t]" . PHP_EOL;
        }
        
        return $str;
    }
    
    /**
     * @param string $str
     * @param string $separator
     * @param string $head
     * @return void
     */
    private function setEndLogStr (string &$str, string $separator, string $head): void
    {
        $endText = '   END LOG   ';
        $count = ceil((mb_strlen(trim($head)) - mb_strlen($endText)) / 2);
        $str .= PHP_EOL;
        $str .= str_repeat($separator, $count);
        $str .= $endText;
        $str .= str_repeat($separator, $count);
    }
    
    /**
     * @param string $message
     * @param mixed $data
     * @param string $channel
     * @return void
     */
    public static function debug (string $message, mixed $data, string $channel = 'log'): void
    {
        $obj = self::getInstance();
        $obj->filename = "{$channel}.log";
        $separator = '=';
        $head = $obj->getHeadLog($obj, $data, $separator);
        $str = $head;
        $str .= "Message: {$message}" . PHP_EOL;
        $str .= "Detail: " . var_export($data, true) . PHP_EOL;
        $obj->setEndLogStr($str, $separator, $head);
        
        file_put_contents($obj->getLogFile(), $str, FILE_APPEND | LOCK_EX);
    }
}