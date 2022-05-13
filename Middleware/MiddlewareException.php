<?php

namespace Gbaski\Core\Middleware;

use Exception;

class MiddlewareException extends Exception
{

    public function __construct($message, $code = 403, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}