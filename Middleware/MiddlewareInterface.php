<?php

namespace Gbaski\Core\Middleware;

use Gbaski\Core\Request;

interface MiddlewareInterface
{
    public function process(Request $request, callable $next);
}