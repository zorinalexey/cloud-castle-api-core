<?php
declare(strict_types = 1);

use CloudCastle\Core\Api\Common\Log\Log;
use CloudCastle\Core\Api\Exceptions\ErrorException;
use CloudCastle\Core\Api\Request\Request;

error_reporting(E_ALL);

require_once "vendor/autoload.php";

set_error_handler(callback : function (int $code, string $message, string $file, int $line){
    throw new ErrorException($code, $message, $file, $line);
});

define('SAPI', sapi_name());
const ROOT_PATH = __DIR__;

try {
    $request = Request::getInstance();
    $request->id = 23;
    
} catch (Throwable $exception) {
    Log::write($exception);
    dump($exception->getMessage());
}

