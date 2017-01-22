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

class TarefaDAO extends GenericDAO
{

    /**
     * TarefaDAO constructor.
     */
    public function __construct()
    {
    }

    public function cadastraNova($tarefaJsonArray, $userId){

        try {

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
            //$stmt->bindParam(':usuario', $tarefaJsonArray['usuarioId'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario', $userId, PDO::PARAM_INT);
            $stmt->execute();
            Conector::$conexao->commit();
            return GenericResponse::buildResponse('TAREFA', "Tarefa cadastrada com sucesso!");
        } catch (Exception $ex) {
            Conector::$conexao->rollBack();
            return GenericResponse::buildResponse('TAREFA', $ex->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            $con->desconectar();
        }
    }

    public function deletaPorId($id){

        //DELETA
        $tarefa = array(["ID" => $id, "PERIODICIDADE" => null]);
        $this->consultaTarefasPeriodicidade($tarefa);

        $sql = "";
        if($tarefa[0]['PERIODICIDADE']['ID'] === 1){
            $sql = "DELETE FROM TAREFA"
                  ." WHERE TAREFA.ID = :id";
        } else {
            $sql = "DELETE FROM TAREFA_PERIODICIDADE, TAREFA"
                  ." USING TAREFA_PERIODICIDADE, TAREFA"
                  ." WHERE TAREFA_PERIODICIDADE.ID = TAREFA.ID_PERIODICIDADE AND"
                  ." TAREFA.ID = :id";
        }

        $con = new Conector;
        $con->conectar();

        $stmt = Conector::$conexao->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return GenericResponse::buildResponse('TAREFA', "Tarefa deletada com sucesso!", HttpStatusCode::OK);
            } else {
                return GenericResponse::buildResponse('TAREFA', "Tarefa não encontrada.", HttpStatusCode::BAD_REQUEST);
            }
        } catch (Exception $e) {
            return GenericResponse::buildResponse('TAREFA', "Erro ao deletar tarefa: " + $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            $con->desconectar();
        }
    }

    public function alteraPorId($tarefaJsonArray){

        //ALTERA
        $tarefa = array(["ID" => $tarefaJsonArray['id'], "PERIODICIDADE" => null]);
        $this->consultaTarefasPeriodicidade($tarefa);

        try {

            $con = new Conector;
            $con->conectar();

            Conector::$conexao->beginTransaction();

            $sql = ""; $stmt = null; $deletarUltimaPeriodicidade = false;
            if ($tarefa[0]['PERIODICIDADE']['ID'] === 1) {

                if (
                (is_null($tarefaJsonArray['periodicidade']['diaSemana']) &&
                    is_null($tarefaJsonArray['periodicidade']['diaMes']) &&
                    is_null($tarefaJsonArray['periodicidade']['diaPartir']) &&
                    $tarefaJsonArray['periodicidade']['passo'] === 1)
                ) {

                    $sql = "UPDATE TAREFA tar"
                        . " SET"
                        . " tar.TITULO = :titulo,"
                        . " tar.ID_GRUPO = :grupo,"
                        . " tar.ID_RESPONSAVEL = :responsavel"
                        . " WHERE tar.ID = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);

                } else {

                    //Insere a periodicidade
                    $periodicidadeSql =
                        "INSERT INTO TAREFA_PERIODICIDADE (ID_DIA_SEMANA, ID_DIA_MES, DT_A_PARTIR, QT_PASSO)"
                        . " VALUES (:diaSemana, :diaMes, :dtPartir, :passo)";

                    $periodicidadeStmt = Conector::$conexao->prepare($periodicidadeSql);

                    $periodicidadeStmt->bindValue(':diaSemana', $tarefaJsonArray['periodicidade']['diaSemana'], PDO::PARAM_INT);
                    $periodicidadeStmt->bindValue(':diaMes', $tarefaJsonArray['periodicidade']['diaMes'], PDO::PARAM_STR);
                    if (!is_null($tarefaJsonArray['periodicidade']['diaPartir'])) {
                        $periodicidadeStmt->bindValue(':dtPartir', date("Y-m-d", $tarefaJsonArray['periodicidade']['diaPartir'] / 1000), PDO::PARAM_STR);
                    } else {
                        $periodicidadeStmt->bindValue(':dtPartir', null, PDO::PARAM_STR);
                    }
                    $periodicidadeStmt->bindValue(':passo', $tarefaJsonArray['periodicidade']['passo'], PDO::PARAM_STR);

                    $periodicidadeStmt->execute();
                    $periodicidadeId = Conector::$conexao->lastInsertId();
                    $tarefaJsonArray['periodicidade']['id'] = $periodicidadeId;

                    $sql = "UPDATE TAREFA tar"
                          ." SET"
                          ." tar.TITULO = :titulo,"
                          ." tar.ID_GRUPO = :grupo,"
                          ." tar.ID_RESPONSAVEL = :responsavel,"
                          ." tar.ID_PERIODICIDADE = :periodicidade"
                          ." WHERE tar.ID = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':periodicidade', $tarefaJsonArray['periodicidade']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);

                }

            } else {

                if (
                !(is_null($tarefaJsonArray['periodicidade']['diaSemana']) &&
                    is_null($tarefaJsonArray['periodicidade']['diaMes']) &&
                    is_null($tarefaJsonArray['periodicidade']['diaPartir']) &&
                    $tarefaJsonArray['periodicidade']['passo'] === 1)
                ) {

                    $sql = "UPDATE TAREFA tar, TAREFA_PERIODICIDADE tarper"
                        . " SET"
                        . " tar.TITULO = :titulo,"
                        . " tar.ID_GRUPO = :grupo,"
                        . " tar.ID_RESPONSAVEL = :responsavel,"
                        . " tarper.ID_DIA_SEMANA = :diaSemana,"
                        . " tarper.ID_DIA_MES = :diaMes,"
                        . " tarper.DT_A_PARTIR = :diaPartir,"
                        . " tarper.QT_PASSO = :passo"
                        . " WHERE tarper.ID = tar.ID_PERIODICIDADE AND"
                        . " tar.ID = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':diaSemana', $tarefaJsonArray['periodicidade']['diaSemana'], PDO::PARAM_STR);
                    $stmt->bindParam(':diaMes', $tarefaJsonArray['diaMes'], PDO::PARAM_STR);
                    $stmt->bindParam(':diaPartir', $tarefaJsonArray['periodicidade']['diaPartir'], PDO::PARAM_STR);
                    $stmt->bindParam(':passo', $tarefaJsonArray['periodicidade']['passo'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);
                } else {

                    $deletarUltimaPeriodicidade = true;

                    $sql = "UPDATE TAREFA tar"
                        ." SET"
                        ." tar.TITULO = :titulo,"
                        ." tar.ID_GRUPO = :grupo,"
                        ." tar.ID_RESPONSAVEL = :responsavel,"
                        ." tar.ID_PERIODICIDADE = :periodicidade"
                        ." WHERE tar.ID = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':periodicidade', 1, PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);
                }
            }

            $stmt->execute();

            if($deletarUltimaPeriodicidade) {
                $periodicidadeSql = "DELETE FROM TAREFA_PERIODICIDADE WHERE ID = :id";

                $periodicidadeStmt = Conector::$conexao->prepare($periodicidadeSql);
                $periodicidadeStmt->bindParam(':id', $tarefa[0]['PERIODICIDADE']['ID'], PDO::PARAM_INT);
                $periodicidadeStmt->execute();
            }

            Conector::$conexao->commit();

            if($stmt->rowCount() > 0){
                return GenericResponse::buildResponse('TAREFA', "Tarefa alterada com sucesso.", HttpStatusCode::OK);
            } else {
                return GenericResponse::buildResponse('TAREFA', "A tarefa não sofreu alterações ou inexiste.", HttpStatusCode::BAD_REQUEST);
            }

        } catch (Exception $e) {
            Conector::$conexao->rollBack();
            return GenericResponse::buildResponse('TAREFA', $e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            $con->desconectar();
        }

    }

    public function consultaPorId($id)
    {
        //NOT USED
    }

    public function consultaTodos()
    {
        //NOT USED
    }

    public function consultaPorIdUsuario($idUsuario)
    {
        $sql = "SELECT tar.ID,"
              ."tar.TITULO,"
              ."tar.ID_PERIODICIDADE AS PERIODICIDADE,"
              ."tar.ID_GRUPO AS GRUPO,"
              ."tar.ID_RESPONSAVEL AS RESPONSAVEL,"
              ."tar.DT_CADASTRO"
              ." FROM TAREFA tar"
              ." WHERE tar.ID_USUARIO = ?"
              ." ORDER BY tar.ID";

        $tarefas = parent::get($sql, false, array($idUsuario));

        $this->consultaTarefasPeriodicidade($tarefas);
        $this->consultaTarefasGrupo($tarefas);
        $this->consultaTarefasResponsavel($tarefas);

        return $tarefas;
    }

    private function consultaTarefasPeriodicidade(&$tarefas)
    {

        $tarefasIds = array_column($tarefas, 'ID');

        $sql = "SELECT tarper.* FROM TAREFA_PERIODICIDADE tarper"
              ." INNER JOIN TAREFA tar ON tar.ID_PERIODICIDADE = tarper.ID"
              ." WHERE tar.ID IN (".implode(", ", $tarefasIds).")"
              ." ORDER BY tar.ID";

        $periodicidades = parent::get($sql, false);
        foreach (is_null($periodicidades) ? array() : $periodicidades as $key => $value) {
            $tarefas[$key]["PERIODICIDADE"] = $value;
        }
    }

    private function consultaTarefasGrupo(&$tarefas)
    {

        $tarefasIds = array_column($tarefas, 'ID');

        $sql = "SELECT gr.* FROM GRUPO gr"
              ." INNER JOIN TAREFA tar ON tar.ID_GRUPO = gr.ID"
              ." WHERE tar.ID IN (".implode(", ", $tarefasIds).")"
              ." ORDER BY tar.ID";

        $grupos = parent::get($sql, false);
        foreach (is_null($grupos) ? array() : $grupos as $key => $value) {
            $tarefas[$key]["GRUPO"] = $value;
        }
    }

    private function consultaTarefasResponsavel(&$tarefas)
    {
        $tarefasIds = array_column($tarefas, 'ID');

        $sql = "SELECT usr.ID, usr.NOME, usr.LOGIN FROM USUARIO usr"
            ." INNER JOIN TAREFA tar ON tar.ID_RESPONSAVEL = usr.ID"
            ." WHERE tar.ID IN (".implode(", ", $tarefasIds).")"
            ." ORDER BY tar.ID";

        $responsaveis = parent::get($sql, false);
        foreach (is_null($responsaveis) ? array() : $responsaveis as $key => $value) {
            $tarefas[$key]["RESPONSAVEL"] = $value;
        }
    }
}