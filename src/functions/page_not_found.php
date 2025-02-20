<?php

use CloudCastle\Core\Api\Response\ApiResponse;
use CloudCastle\Core\Api\Router\RouteException;

function page_not_found()
{
    throw new RouteException(trans('router.404 - Page not found!!!'), 404);
}