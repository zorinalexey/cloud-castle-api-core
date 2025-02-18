<?php

namespace CloudCastle\Core\Api\Common\Filters;

use CloudCastle\Core\Api\Common\DB\AbstractBuilder;

final class TokenFilter extends AbstractBuilder
{
    protected array $fillable = [
        'user_uuid',
        'user_agent',
        'user_ip',
        'token',
        'expires_at',
    ];
    
    protected function setAddFilterParams (array &$filters): void
    {
    }
}