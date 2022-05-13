<?php

namespace Gbaski\Core\Middleware;

use Gbaski\Core\Request;

class MiddlewareStack
{

    protected $next;

    public function __construct()
    {
        $this->next = function(Request $request){

           return $request;
        };
    }


    public function add($middleware)
    {

        $next = $this->next;

        $this->next = function(Request $request) use ($middleware, $next){

            return call_user_func([is_object($middleware) ? $middleware : new $middleware, 'process'], $request, $next);
        };
        
    }

    public function handle(Request $request)
    {
        return call_user_func($this->next, $request);
    }

}