<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 3:46 AM
 */

namespace Util;

use Enum\HttpStatusCode;

class GenericResponse
{

    public static function buildResponse($operacao, $body, $status = HttpStatusCode::OK){

        $response = ['response' =>
                [
                  'status' => $status,
                  'operation' => $operacao,
                  'body' => $body
                ]
               ];

        return json_encode($response);

    }


}