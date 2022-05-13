<?php

declare(strict_types=1);

namespace Gbaski\Core;

use Gbaski\Core\Middleware\MiddlewareException;
use Gbaski\Core\Middleware\MiddlewareStack;
use Throwable;

class App extends Router
{
    public static App $app;
    public static array $config;
    public static object $brand;

    protected MiddlewareStack $middlewareStack;

    public function __construct($config = [])
    {

        self::$config = $config;
        parent::__construct(new Request(), new Response());

        $this->middlewareStack = $this->middlewares(self::$config['middlewares'] ?? []);

        self::$app = $this;

    }

    public function __invoke($request): self
    {
        echo "App Init\n";
        $_REQUEST = $request;
        $app = $this;
        require 'routes.php';

        return $this;
    }

    public function run()
    {
        try {

            $this->request = $this->middlewareStack->handle($this->request, $this->response);
            return $this->resolve();

        }

        catch (MiddlewareException $m){
            return $this->response->output([], $m->getMessage(), $m->getCode());
        }
        catch (Throwable $th){

            return $this->response->output([], 'Error', 500, $th->getMessage());
        }

    }

}