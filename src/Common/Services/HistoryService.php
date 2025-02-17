<?php

namespace CloudCastle\Core\Api\Common\Services;

use CloudCastle\Core\Api\Common\Filters\HistoryFilter;

final class HistoryService extends AbstractService
{
    public function __construct (string $dbName = 'default')
    {
        $this->table = 'histories';
        $this->dbName = $dbName;
        $this->filter = HistoryFilter::class;
        
        parent::__construct();
    }
    
    protected function di (): array
    {
        return [];
    }
}