<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/20/2017
 * Time: 11:47 PM
 */

namespace Dao;

use PDO;
use Exception;
use Util\GenericResponse;
use Enum\HttpStatusCode;

class TarefaDAO
{

    /**
     * TarefaDAO constructor.
     */
    public function __construct()
    {
    }

    public function cadastraNova($tarefaJsonArray){

        try {

            /*

            $sth = $dbh->exec("DROP TABLE fruit");
            $sth = $dbh->exec("UPDATE dessert SET name = 'hamburger'");

            */

            $con = new Conector;
            $con->conectar();

            Conector::$conexao->beginTransaction();

            //Verifica se a periodicidade e diaria
            if (
            !(is_null($tarefaJsonArray['periodicidade']['diaSemana']) &&
                is_null($tarefaJsonArray['periodicidade']['diaMes']) &&
                is_null($tarefaJsonArray['periodicidade']['diaPartir']) &&
                $tarefaJsonArray['periodicidade']['passo'] === 1)
            ) {
                //Insere a periodicidade
                $sql =
                    "INSERT INTO TAREFA_PERIODICIDADE (ID_DIA_SEMANA, ID_DIA_MES, DT_A_PARTIR, QT_PASSO)"
                    . " VALUES (:diaSemana, :diaMes, :dtPartir, :passo)";

                $stmt = Conector::$conexao->prepare($sql);

                $stmt->bindValue(':diaSemana', $tarefaJsonArray['periodicidade']['diaSemana'], PDO::PARAM_INT);
                $stmt->bindValue(':diaMes', $tarefaJsonArray['periodicidade']['diaMes'], PDO::PARAM_STR);
                if (!is_null($tarefaJsonArray['periodicidade']['diaPartir'])) {
                    $stmt->bindValue(':dtPartir', date("Y-m-d", $tarefaJsonArray['periodicidade']['diaPartir'] / 1000), PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(':dtPartir', null, PDO::PARAM_STR);
                }
                $stmt->bindValue(':passo', $tarefaJsonArray['periodicidade']['passo'], PDO::PARAM_STR);

                $stmt->execute();
                $periodicidadeId = Conector::$conexao->lastInsertId();
                $tarefaJsonArray['periodicidade']['id'] = $periodicidadeId;

            }

            //Insere a tarefa
            $sql =
                "INSERT INTO TAREFA (TITULO, ID_PERIODICIDADE, ID_GRUPO, ID_RESPONSAVEL, ID_USUARIO, DT_CADASTRO)
                 VALUES (:titulo, :periodicidade, :grupo, :responsavel, :usuario, NOW())";

            $stmt = Conector::$conexao->prepare($sql);

            $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);

            if (!is_null($tarefaJsonArray['periodicidade']['id'])) {
                $stmt->bindParam(':periodicidade', $tarefaJsonArray['periodicidade']['id'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':periodicidade', 1, PDO::PARAM_STR);
            }

            $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_STR);
            $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_STR);
            $stmt->bindParam(':usuario', $tarefaJsonArray['usuarioId'], PDO::PARAM_INT);
            $stmt->execute();
            Conector::$conexao->commit();
        } catch (Exception $ex) {
            Conector::$conexao->rollBack();
            return GenericResponse::buildResponse('TAREFA', $ex->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            $con->desconectar();
        }

        return GenericResponse::buildResponse('TAREFA', "Tarefa cadastrada com sucesso!");
    }

    public function deletaPorId($id){
        $con = new Conector;
        $con->conectar();

        $con->desconectar();
    }
}