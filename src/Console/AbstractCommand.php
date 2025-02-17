<?php

namespace CloudCastle\Core\Api\Console;

abstract class AbstractCommand implements CommandInterface
{
    abstract public function handle(): bool;
}