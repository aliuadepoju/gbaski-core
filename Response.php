<?php

namespace Gbaski\Core;

use Gbaski\Core\Middleware\MiddlewareException;

class Response
{

    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function output(array $data, string $message, int $status_code = 200, string $err = null)
    {

        $this->setStatusCode($status_code);

        $payload = [
            'status' => in_array($status_code, [200, 201]) ? true : false,
            'message' => $message
        ];

        if(!empty($data)) $payload['data'] = $data;
        if(!is_null($err)) $payload['error'] = $err;

//        echo json_encode($payload);
        return $payload;
    }


    public function throwException($message, $code = 403)
    {

        throw new MiddlewareException($message, $code);
    }

}