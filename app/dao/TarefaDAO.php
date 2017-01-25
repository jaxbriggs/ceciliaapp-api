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
            !(is_null($tarefaJsonArray['periodicidade']['id_dia_semana']) &&
                is_null($tarefaJsonArray['periodicidade']['id_dia_mes']) &&
                is_null($tarefaJsonArray['periodicidade']['dt_a_partir']) &&
                $tarefaJsonArray['periodicidade']['qt_passo'] === 1)
            ) {
                //Insere a periodicidade
                $sql =
                    "INSERT INTO tarefa_periodicidade (id_dia_semana, id_dia_mes, dt_a_partir, qt_passo)"
                    . " VALUES (:diaSemana, :diaMes, :dtPartir, :passo)";

                $stmt = Conector::$conexao->prepare($sql);

                $stmt->bindValue(':diaSemana', $tarefaJsonArray['periodicidade']['id_dia_semana'], PDO::PARAM_INT);
                $stmt->bindValue(':diaMes', $tarefaJsonArray['periodicidade']['id_dia_mes'], PDO::PARAM_STR);
                if (!is_null($tarefaJsonArray['periodicidade']['dt_a_partir'])) {
                    $stmt->bindValue(':dtPartir', date("Y-m-d", $tarefaJsonArray['periodicidade']['dt_a_partir'] / 1000), PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(':dtPartir', null, PDO::PARAM_STR);
                }
                $stmt->bindValue(':passo', $tarefaJsonArray['periodicidade']['qt_passo'], PDO::PARAM_STR);

                $stmt->execute();
                $periodicidadeId = Conector::$conexao->lastInsertId();
                $tarefaJsonArray['periodicidade']['id'] = $periodicidadeId;

            }

            //Insere a tarefa
            $sql =
                "INSERT INTO tarefa (titulo, id_periodicidade, id_grupo, id_responsavel, id_usuario, dt_cadastro)
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
            $stmt->bindParam(':usuario', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $lastId = Conector::$conexao->lastInsertId();
            Conector::$conexao->commit();
            return GenericResponse::buildResponse('TAREFA', "Tarefa cadastrada com sucesso!", $this->consultaPorId($lastId));
        } catch (Exception $ex) {
            Conector::$conexao->rollBack();
            return GenericResponse::buildResponse('TAREFA', $ex->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            $con->desconectar();
        }
    }

    public function deletaPorId($id){

        //DELETA
        $tarefa = array(["id" => $id, "periodicidade" => null]);
        $this->consultaTarefasPeriodicidade($tarefa);

        $sql = "";
        if($tarefa[0]['periodicidade']['id'] === 1){
            $sql = "DELETE FROM tarefa"
                  ." WHERE tarefa.id = :id";
        } else {
            $sql = "DELETE FROM tarefa_periodicidade, tarefa"
                  ." USING tarefa_periodicidade, tarefa"
                  ." WHERE tarefa_periodicidade.id = tarefa.id_periodicidade AND"
                  ." tarefa.id = :id";
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
        $tarefa = array(["id" => $tarefaJsonArray['id'], "periodicidade" => null]);
        $this->consultaTarefasPeriodicidade($tarefa);

        try {

            $con = new Conector;
            $con->conectar();

            Conector::$conexao->beginTransaction();

            $sql = ""; $stmt = null; $deletarUltimaPeriodicidade = false;
            if ($tarefa[0]['periodicidade']['id'] === 1) {

                if (
                (is_null($tarefaJsonArray['periodicidade']['id_dia_semana']) &&
                    is_null($tarefaJsonArray['periodicidade']['id_dia_mes']) &&
                    is_null($tarefaJsonArray['periodicidade']['dt_a_partir']) &&
                    $tarefaJsonArray['periodicidade']['qt_passo'] === 1)
                ) {

                    $sql = "UPDATE tarefa tar"
                        . " SET"
                        . " tar.titulo = :titulo,"
                        . " tar.id_grupo = :grupo,"
                        . " tar.id_responsavel = :responsavel"
                        . " WHERE tar.id = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);

                } else {

                    //Insere a periodicidade
                    $periodicidadeSql =
                        "INSERT INTO tarefa_periodicidade (id_dia_semana, id_dia_mes, dt_a_partir, qt_passo)"
                        . " VALUES (:diaSemana, :diaMes, :dtPartir, :passo)";

                    $periodicidadeStmt = Conector::$conexao->prepare($periodicidadeSql);

                    $periodicidadeStmt->bindValue(':diaSemana', $tarefaJsonArray['periodicidade']['id_dia_semana'], PDO::PARAM_INT);
                    $periodicidadeStmt->bindValue(':diaMes', $tarefaJsonArray['periodicidade']['id_dia_mes'], PDO::PARAM_STR);
                    if (!is_null($tarefaJsonArray['periodicidade']['dt_a_partir'])) {
                        $periodicidadeStmt->bindValue(':dtPartir', date("Y-m-d", $tarefaJsonArray['periodicidade']['dt_a_partir'] / 1000), PDO::PARAM_STR);
                    } else {
                        $periodicidadeStmt->bindValue(':dtPartir', null, PDO::PARAM_STR);
                    }
                    $periodicidadeStmt->bindValue(':passo', $tarefaJsonArray['periodicidade']['qt_passo'], PDO::PARAM_STR);

                    $periodicidadeStmt->execute();
                    $periodicidadeId = Conector::$conexao->lastInsertId();
                    $tarefaJsonArray['periodicidade']['id'] = $periodicidadeId;

                    $sql = "UPDATE tarefa tar"
                          ." SET"
                          ." tar.titulo = :titulo,"
                          ." tar.id_grupo = :grupo,"
                          ." tar.id_responsavel = :responsavel,"
                          ." tar.id_periodicidade = :periodicidade"
                          ." WHERE tar.id = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':periodicidade', $tarefaJsonArray['periodicidade']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);

                }

            } else {

                if (
                !(is_null($tarefaJsonArray['periodicidade']['id_dia_semana']) &&
                    is_null($tarefaJsonArray['periodicidade']['id_dia_mes']) &&
                    is_null($tarefaJsonArray['periodicidade']['dt_a_partir']) &&
                    $tarefaJsonArray['periodicidade']['qt_passo'] === 1)
                ) {

                    $sql = "UPDATE tarefa tar, tarefa_periodicidade tarper"
                        . " SET"
                        . " tar.titulo = :titulo,"
                        . " tar.id_grupo = :grupo,"
                        . " tar.id_responsavel = :responsavel,"
                        . " tarper.id_dia_semana = :diaSemana,"
                        . " tarper.id_dia_mes = :diaMes,"
                        . " tarper.dt_a_partir = :diaPartir,"
                        . " tarper.qt_passo = :passo"
                        . " WHERE tarper.id = tar.id_periodicidade AND"
                        . " tar.ID = :tarefa";

                    $stmt = Conector::$conexao->prepare($sql);
                    $stmt->bindParam(':titulo', $tarefaJsonArray['titulo'], PDO::PARAM_STR);
                    $stmt->bindParam(':grupo', $tarefaJsonArray['grupo']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel', $tarefaJsonArray['responsavel']['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':diaSemana', $tarefaJsonArray['periodicidade']['id_dia_semana'], PDO::PARAM_STR);
                    $stmt->bindParam(':diaMes', $tarefaJsonArray['periodicidade']['id_dia_mes'], PDO::PARAM_STR);
                    $stmt->bindParam(':diaPartir', $tarefaJsonArray['periodicidade']['dt_a_partir'], PDO::PARAM_STR);
                    $stmt->bindParam(':passo', $tarefaJsonArray['periodicidade']['qt_passo'], PDO::PARAM_INT);
                    $stmt->bindParam(':tarefa', $tarefaJsonArray['id'], PDO::PARAM_INT);
                } else {

                    $deletarUltimaPeriodicidade = true;

                    $sql = "UPDATE tarefa tar"
                        ." SET"
                        ." tar.titulo = :titulo,"
                        ." tar.id_grupo = :grupo,"
                        ." tar.id_responsavel = :responsavel,"
                        ." tar.id_periodicidade = :periodicidade"
                        ." WHERE tar.id = :tarefa";

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
                $periodicidadeStmt->bindParam(':id', $tarefa[0]['periodicidade']['id'], PDO::PARAM_INT);
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
        $sql = "SELECT tar.id,"
            ."tar.titulo,"
            ."tar.id_periodicidade AS periodicidade,"
            ."tar.id_grupo AS grupo,"
            ."tar.id_responsavel AS responsavel,"
            ."tar.dt_cadastro"
            ." FROM tarefa tar"
            ." WHERE tar.ID = ?";

        $tarefa = parent::get($sql, true, array($id));

        $tarefas = array($tarefa);

        if(!is_null($tarefas)) {
            $this->consultaTarefasPeriodicidade($tarefas);
            $this->consultaTarefasGrupo($tarefas);
            $this->consultaTarefasResponsavel($tarefas);
        }

        return $tarefas[0];
    }

    public function consultaTodos()
    {
        //NOT USED
    }

    public function consultaPorIdUsuario($idUsuario)
    {
        $sql = "SELECT tar.id,"
              ."tar.titulo,"
              ."tar.id_periodicidade AS periodicidade,"
              ."tar.id_grupo AS grupo,"
              ."tar.id_responsavel AS responsavel,"
              ."tar.dt_cadastro"
              ." FROM tarefa tar"
              ." WHERE tar.id_usuario = ?"
              ." ORDER BY tar.id";

        $tarefas = parent::get($sql, false, array($idUsuario));

        if(!is_null($tarefas)) {
            $this->consultaTarefasPeriodicidade($tarefas);
            $this->consultaTarefasGrupo($tarefas);
            $this->consultaTarefasResponsavel($tarefas);
        }

        return $tarefas;
    }

    private function consultaTarefasPeriodicidade(&$tarefas)
    {

        $tarefasIds = array_column($tarefas, 'id');

        $sql = "SELECT tarper.* FROM tarefa_periodicidade tarper"
              ." INNER JOIN tarefa tar ON tar.id_periodicidade = tarper.id"
              ." WHERE tar.id IN (".implode(", ", $tarefasIds).")"
              ." ORDER BY tar.id";

        $periodicidades = parent::get($sql, false);
        foreach (is_null($periodicidades) ? array() : $periodicidades as $key => $value) {
            $tarefas[$key]["periodicidade"] = $value;
        }
    }

    private function consultaTarefasGrupo(&$tarefas)
    {

        $tarefasIds = array_column($tarefas, 'id');

        $sql = "SELECT gr.* FROM grupo gr"
              ." INNER JOIN tarefa tar ON tar.id_grupo = gr.id"
              ." WHERE tar.id IN (".implode(", ", $tarefasIds).")"
              ." ORDER BY tar.id";

        $grupos = parent::get($sql, false);
        foreach (is_null($grupos) ? array() : $grupos as $key => $value) {
            $tarefas[$key]["grupo"] = $value;
        }
    }

    private function consultaTarefasResponsavel(&$tarefas)
    {
        $tarefasIds = array_column($tarefas, 'id');

        $sql = "SELECT usr.id, usr.nome, usr.login FROM usuario usr"
            ." INNER JOIN tarefa tar ON tar.id_responsavel = usr.id"
            ." WHERE tar.id IN (".implode(", ", $tarefasIds).")"
            ." ORDER BY tar.id";

        $responsaveis = parent::get($sql, false);
        foreach (is_null($responsaveis) ? array() : $responsaveis as $key => $value) {
            $tarefas[$key]["responsavel"] = $value;
        }
    }
}