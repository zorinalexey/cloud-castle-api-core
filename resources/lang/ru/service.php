<?php

use CloudCastle\Core\Api\Common\DB\AbstractBuilder;

return [
    'Table name not set' => 'Не задано наименование таблицы',
    'service.Filter must be defined or not implemented ' . AbstractBuilder::class => 'Не задан класс фильтра или он не наследует абстрактный класс ' . AbstractBuilder::class,
];