<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/5/2017
 * Time: 11:17 PM
 */

namespace Controller;

use Dao\UsuarioDAO;

class LoginController
{
    /**
     * LoginController constructor.
     */
    public function __construct()
    {

    }

    public function login ($login, $senha) {

        $usuarioDao = new UsuarioDAO;
        $usuario = $usuarioDao->consultaPorLogin($login);

        if($usuario && md5($senha) == $usuario->getSenha()){
            return $usuario;
        }

        return false;
    }
}