<?php

namespace CloudCastle\Core\Api\Response;

final class ApiResponse
{
    private readonly ResponseInterface $response;
    
    public function __construct(bool $success, array $data, array $errors = [], string|null $message = null, int $code = 200, array $options = [])
    {
        $contentType = content_type();
        
        $namespace = match ($contentType) {
            'application/json' => '\\CloudCastle\\Core\\Api\\Response\\Json\\',
            'application/xml', 'text/xml' => '\\CloudCastle\\Core\\Api\\Response\\Xml\\',
            default => '\\CloudCastle\\Core\\Api\\Response\\Html\\',
        };
        
        $type = match ($success) {
            true => 'SuccessResponse',
            default => 'FailResponse',
        };
        
        $this->response = new ($namespace.$type)($data, $errors, $message, $code, $options);
    }
    
    public function __toString(): string
    {
        return (string)$this->response;
    }
}