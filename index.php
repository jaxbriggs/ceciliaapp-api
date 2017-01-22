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
use Dao\GrupoDAO;

$apiBase = \Dao\Conector::$isProduction ? "" : "/ceciliaapp-api";
$klein = new \Klein\Klein();
$conector = new \Dao\Conector();

/*USUARIOS*/
$klein->with($apiBase.'/user', function () use ($klein) {

    //GET - Todos os usuarios
    $klein->respond('GET', '/?', function ($request) {
        $auth = $request->headers()->all()['Authorization'];
        $user = Token::checkToken($auth);
        if($user){
            $dao = new UsuarioDAO;
            $todos = $dao->consultaTodos();
            return json_encode($todos);
        }
    });

    //GET - Tarefas de um usuario
    $klein->respond('GET', '/tarefas', function ($request) {
        $auth = $request->headers()->all()['Authorization'];
        $user = Token::checkToken($auth);
        if($user){
            $dao = new TarefaDAO();
            $usuarioTarefas = $dao->consultaPorIdUsuario($user->getId());
            return json_encode($usuarioTarefas);
        }
    });

    //POST - Login
    $klein->respond('POST', '/login', function ($request) {

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

});

/*TAREFAS*/
$klein->with($apiBase.'/tarefa', function () use ($klein) {

    //PUT - Nova tarefa
    $klein->respond('PUT', '/?', function ($request) {

        $auth = $request->headers()->all()['Authorization'];
        $user = Token::checkToken($auth);
        if($user){

            //Pega a tarefa da requisicao
            $body = $request->body();

            $tarefaJson = json_decode(json_decode($body, true)['tarefa'], true);
            $dao = new TarefaDAO();
            return $dao->cadastraNova($tarefaJson, $user->getId());
        }

    });

    //DELETE - Deleta tarefa
    $klein->respond('DELETE', '/[i:id]', function ($request) {
        $auth = $request->headers()->all()['Authorization'];
        if(Token::checkToken($auth)){
            $dao = new TarefaDAO();
            return $dao->deletaPorId($request->id);
        }
    });

    //POST - Altera tarefa
    $klein->respond('POST', '/?', function ($request) {
        $auth = $request->headers()->all()['Authorization'];
        if(Token::checkToken($auth)){

            //Pega a tarefa da requisicao
            $body = $request->body();

            $tarefaJson = json_decode(json_decode($body, true)['tarefa'], true);

            $dao = new TarefaDAO();
            return $dao->alteraPorId($tarefaJson);
        }
    });

});

/*GRUPOS*/
$klein->with($apiBase.'/grupo', function () use ($klein) {

    //GET - Nova tarefa
    $klein->respond('GET', '/?', function ($request) {
        $auth = $request->headers()->all()['Authorization'];
        if(Token::checkToken($auth)){
            $dao = new GrupoDAO();
            return json_encode($dao->consultaTodos());
        }
    });

});

$klein->dispatch();