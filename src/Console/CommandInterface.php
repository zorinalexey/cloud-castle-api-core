<?php

namespace CloudCastle\Core\Api\Console;

interface CommandInterface
{
    public function handle(): bool;
}