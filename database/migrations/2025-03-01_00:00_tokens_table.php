<?php

use CloudCastle\Core\Api\Common\Services\HistoryService;

return new class {
    
    private string $serviceClass = HistoryService::class;
    
    public function up (): bool
    {
        
        return false;
    }
    
    public function down (): bool
    {
        
        
        return false;
    }
};