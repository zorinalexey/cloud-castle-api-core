<?php

namespace CloudCastle\Core\Api\Common\Services;

use CloudCastle\Core\Api\Common\Filters\TokenFilter;

final class TokenService extends AbstractService
{
    public function __construct (string $dbName = 'default')
    {
        $this->table = 'tokens';
        $this->dbName = $dbName;
        $this->filter = TokenFilter::class;
        
        parent::__construct();
    }
    
    protected function di (): array
    {
        return [];
    }
}