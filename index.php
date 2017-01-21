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
use Dao\TarefaDAO;

$apiBase = \Dao\Conector::$isProduction ? "" : "/ceciliaapp-api";
$klein = new \Klein\Klein();
$conector = new \Dao\Conector();

//Usuario
$klein->respond('GET', $apiBase.'/user', function ($request) {

    $auth = $request->headers()->all()['Authorization'];
    $user = Token::checkToken($auth);
    if($user){
        $dao = new UsuarioDAO;
        $todos = $dao->consultaTodos();
        return json_encode($todos);
    }
});

//Login
$klein->respond('POST', $apiBase.'/user/login', function ($request) {

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

//Tarefa
$klein->respond('POST', $apiBase.'/tarefa/nova', function ($request) {

    $auth = $request->headers()->all()['Authorization'];
    if(Token::checkToken($auth)){

        //Pega a tarefa da requisicao
        $body = $request->body();

        $tarefaJson = json_decode(json_decode($body, true)['tarefa'], true);
        $dao = new TarefaDAO();
        return $dao->cadastraNova($tarefaJson);
    }

});

//DON'T DELETE
/*
 *
 */

$klein->dispatch();