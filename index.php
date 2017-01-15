<?php
/**
 * Created by PhpStorm.
 * Manipula todos os requests a API
 * User: Carlos Henrique
 * Date: 1/5/2017
 * Time: 10:39 PM
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/requires.php';

use Util\GenericResponse;

use Controller\LoginController;

use Jwt\Token;

use Dao\UsuarioDAO;

$klein = new \Klein\Klein();

$conector = new \Dao\Conector();

//Usuario
//$klein->respond('POST', '/ceciliaapp-api/user', function ($request) {
$klein->respond('POST', '/user', function ($request) {

    $auth = $request->headers()->all()['Authorization'];
    $user = Token::checkToken($auth);
    if($user){
        $dao = new UsuarioDAO;
        $todos = $dao->consultaTodos();
        return json_encode($todos);
    }
});

//Login
//$klein->respond('POST', '/ceciliaapp-api/user/login', function ($request) {
$klein->respond('POST', '/user/login', function ($request) {

    $requester = json_decode($request->body(), true);

    $login_controller = new LoginController();

    $usuario = $login_controller->login($requester['login'], $requester['senha']);

    if ($usuario) {

        $token = Token::gerar([
            'usuarioId'   => $usuario->getId(),
            'usuarioNome' => $usuario->getNome(),
        ]);

        return $token;
    }

    return GenericResponse::buildResponse('LOGIN', "Usuário e/ou senha incorretos.");

});

//DON'T DELETE
/*
 *
 */

$klein->dispatch();