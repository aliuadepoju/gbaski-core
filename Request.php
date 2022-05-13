<?php

namespace Gbaski\Core;

use stdClass;

class Request
{

    public object $data;
    public array $params = [];
    private int $version;
    public bool $api_key_required = true;

    public function getPath(): string
    {

        preg_match("/(\d{1,2})\/([\/\-\_a-z\d=]+)+/i",$_REQUEST->server['request_uri'],$matches);

        $this->version = @$matches[1] ?? 1;

        $path = '/'.@$matches[2];
        $position = strpos($path, '?');

        if($position === false){
            return $path;
        }

        return substr($path, 0, $position);

    }

    public function getMethod(): string
    {
        return strtolower(@$_REQUEST->server['request_method']);
    }

    public function getParams($rule_uri, $query_uri): array
    {

        $params = [];

        $rule_uri_arr = explode('/', $rule_uri);
        $query_uri_arr = explode('/', $query_uri);

        for ($i = 0;$i < count($rule_uri_arr); $i++){

            if($query_uri_arr[$i] != $rule_uri_arr[$i]) $params[substr($rule_uri_arr[$i], 1)] = $query_uri_arr[$i];
        }

        if(is_null($_REQUEST->files)){

            $stdin = $_REQUEST->rawContent();

            if(!empty($stdin)) {

                parse_str($stdin, $std_arr);
                $params = array_merge($params,(is_null(json_decode($stdin,1)))?($std_arr):(json_decode($stdin,1)));

            }
        }


        $params = array_merge($params,$_REQUEST->get ?? []);
        $params = array_merge($params,$_REQUEST->post ?? []);
        $params = array_merge($params,$_REQUEST->cookie ?? []);

        return $params;
    }

    public function getHeader(string $key): string
    {
        return $_REQUEST->header[$key] ?? '';
    }

    public function getApiVersion(): int
    {
        return $this->version;
    }

}