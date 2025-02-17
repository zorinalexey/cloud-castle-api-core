<?php

namespace CloudCastle\Core\Api\Common\Filters;

use CloudCastle\Core\Api\Common\DB\AbstractBuilder;

final class HistoryFilter extends AbstractBuilder
{
    protected array $fillable = [
        'table',
        'service',
        'action',
        'before',
        'after',
    ];
    
    protected function setAddFilters (array &$filters): void
    {
    }
}