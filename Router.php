<?php
declare(strict_types=1);

namespace Gbaski\Core;

use Gbaski\Core\Middleware\MiddlewareStack;
use Gbaski\Middleware\AuthMiddleware;
use stdClass;

class Router
{

    protected array $registry = [];
    public Request $request;
    public Response $response;

    public array $middlewareMap = [];

    protected MiddlewareStack $middlewareStack;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

//        $this->middlewareStack = new MiddlewareStack();

    }

    public function middlewares(array $middlewares, string $path = null)
    {

        if(!is_null($path)){

            $this->middlewareMap[$path] = $middlewares;

        }else{

            $this->middlewareStack = new MiddlewareStack();

            $middlewares = array_reverse($middlewares);

            foreach ($middlewares as $middleware){
                $this->middlewareStack->add($middleware);
            }

            return $this->middlewareStack;
        }

    }

    public function get($uri, $callback, $config = []): self
    {
        $this->register($uri, $callback, __FUNCTION__, $config);
        return $this;
    }

    public function post($uri, $callback, $config = []): self
    {
        $this->register($uri, $callback, __FUNCTION__, $config);
        return $this;
    }

    public function put($uri, $callback, $config = []): self
    {
        $this->register($uri, $callback, __FUNCTION__, $config);
        return $this;
    }

    public function delete($uri, $callback, $config = []): self
    {
        $this->register($uri, $callback, __FUNCTION__, $config);
        return $this;
    }


    private function register($rule_uri, $callback, $method, $config)
    {

        if(is_array($rule_uri)){
            foreach ($rule_uri as $uri) {
                $this->register($uri, $callback, $method, $config);
            }

            return $this;
        }

        //,"param"=>null,"path"=>$uri

        $resource = new stdClass();
        $resource->key_required = App::$config['auth']['key_required'];
        $resource->token_required = App::$config['auth']['token_required'];
        $resource->callback = $callback;
        $resource->method = $method;
        $resource->config = $config;

        if(str_starts_with($rule_uri, "!")){

            $resource->key_required = false;
            $rule_uri = substr($rule_uri,1);

        }

        if(str_ends_with($rule_uri, "!")){

            $resource->token_required = false;
            $rule_uri = substr($rule_uri, 0,-1);

            echo "rule_uri: $rule_uri\n";

        }

        $resource->rule_uri = $rule_uri;

        $rule_uri_class = strtolower(explode("/",$rule_uri)[1]);

        $this->registry[$method][$rule_uri_class][$rule_uri] = $resource;//Route data-structure

        return $this;
    }

    public function resolve()
    {

        $query_uri = $this->request->getPath();
        $query_method = $this->request->getMethod();
        $query_uri_class = strtolower(explode("/",$query_uri)[1]);

        foreach ($this->middlewareMap as $path => $middlewares){

            $pattern = "/^".str_replace(["/"],["\/"], $path)."$/i";

            if(preg_match($pattern, $query_uri, $matches)){

                $this->request = $this->middlewares($middlewares ?? [])->handle($this->request);

            }
        }

//        print('<pre>');
//        print_r([$query_uri_class, $this->registry]);
//        print('<pre/>');

        $registry = $this->registry[$query_method][$query_uri_class] ?? false;

//        var_dump($registry);

        if($registry === false){

            return $this->response->throwException('err msg here', 404);
        }

        $resource = @$registry[$query_uri];

        if(isset($resource)){

            return $this->execute($resource, $this->request->getParams($resource->rule_uri, $query_uri));
        }
        else{

            foreach($registry as $rule_uri => $resource){

                if(count(explode("/",$rule_uri)) != count(explode("/", $query_uri))){

                    continue;
                }

                $pattern = "/^".str_replace(["/"],["\/"], preg_replace("/:[-_a-zA-Z0-9=]+/","([-_a-zA-Z0-9=]+)",$rule_uri))."$/i";

                $has_path_params = preg_match($pattern, $query_uri, $matches);

                if($has_path_params === 1) {

                    // return $this->response(['status' => false,'message' =>[$has_path_params, $matches, $pattern]]);
                    return $this->execute($resource, $this->request->getParams($rule_uri, $query_uri));
                    break;
                }

            }
        }

        return $this->response->throwException('err msg here', 404);

    }


    public function execute($resource, $params = [])
    {
        //        var_dump($resource->callback);

        $resource->config['middlewares'] = [new AuthMiddleware($resource->key_required, $resource->token_required), ...($resource->config['middlewares'] ?? [])];

        $this->request = $this->middlewares($resource->config['middlewares'])->handle($this->request);

        $params = array_merge($params, $this->request->params);

        $this->request->data = (object)$params;

        $rule_uri_class = ucfirst(explode("/",$resource->rule_uri)[1]);

        $class_namespace = sprintf('Gbaski\Controllers\%s', $rule_uri_class);
        $model_namespace = sprintf('%s\Model', $class_namespace);

        $model = new $model_namespace;

        if(is_object($resource->callback)) {

            $class = $resource->callback;

        }

        if(is_string($resource->callback)) {

            list($callback_class, $callback_action) = explode("@", $resource->callback);

            $callback_class = str_starts_with($callback_class, 'Gbaski\Controllers') ? $callback_class : sprintf('Gbaski\Controllers\%s', $callback_class);

            $instance = new $callback_class(...[$model, new \GuzzleHttp\Client()]);

            $class = [$instance, $callback_action];

     }

        if(is_array($resource->callback)) {

            $callback_class = $resource->callback[0];
            $callback_action = $resource->callback[1];

            $instance = new $callback_class(...[$model, new \GuzzleHttp\Client()]);

            $class = [$instance, $callback_action];

        }

        return call_user_func($class, $this->request, $this->response);

    }

}