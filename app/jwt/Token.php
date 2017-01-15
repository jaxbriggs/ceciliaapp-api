<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 6:34 PM
 */

namespace Jwt;

use Enum\HttpStatusCode;
use JWT;
use Exception;
use Model\Usuario;
use Util\GenericResponse;

class Token
{
    public static function gerar($conteudo){

        $tokenId    = base64_encode(mcrypt_create_iv(32));
        $issuedAt   = time();
        $serverName = gethostname(); // Retrieve the server name

        /*
         * Create the token as an array
         */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'data' => $conteudo
        ];

        $gertrudes = file_get_contents(getcwd()."\\app\\jwt\\gertrudes.txt");
        $secretKey = base64_decode($gertrudes);

        /*
         * Encode the array to a JWT string.
         * Second parameter is the key to encode the token.
         *
         * The output string can be validated at http://jwt.io/
         */
        $jwt = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        $unencodedArray = ['token' => $jwt];
        return json_encode($unencodedArray);

    }

    public static function checkToken($token)
    {
        if($token) {

            try {
                $gertrudes = file_get_contents(getcwd() . "\\app\\jwt\\gertrudes.txt");
                $secretKey = base64_decode($gertrudes);

                $token = JWT::decode($token, $secretKey, array('HS512'));

                //Pega o usuário à partir dos dados
                $usuario = new Usuario;
                $usuario->setId($token->data->usuarioId);
                $usuario->setNome($token->data->usuarioNome);

                return $usuario;
            } catch (Exception $e) {
                echo GenericResponse::buildResponse("TOKEN", "Erro de autenticação.", HttpStatusCode::UNAUTHORIZED);
                return null;
            }

        } else {
            echo GenericResponse::buildResponse("TOKEN", "Este serviço exige autenticação.", HttpStatusCode::BAD_REQUEST);
            return null;
        }
    }
}