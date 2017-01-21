<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/20/2017
 * Time: 11:47 PM
 */

namespace Dao;

use Model\Tarefa;

class TarefaDAO
{

    /**
     * TarefaDAO constructor.
     */
    public function __construct()
    {
    }

    public function cadastraNova($tarefa){
        $con = new Conector;
        $con->conectar();

        //Verifica se a periodicidade e diaria
        $isDiaria = (
            !$tarefa->getPeriodicidade()->getDiaSemana() &&
            !$tarefa->getPeriodicidade()->getDiaMes() &&
            !$tarefa->getPeriodicidade()->getDiaPartir() &&
            $tarefa->getPeriodicidade()->getPasso() == 1
        );

        if(!$isDiaria){

        } else {

            $sql = 'SELECT ID FROM TAREFA_PERIODICIDADE
                    WHERE ID_DIA_SEMANA IS NULL
                    AND ID_DIA_MES IS NULL
                    AND DT_A_PARTIR IS NULL
                    AND QT_PASSO = 1';

            $tarefa->getPeriodicidade()->setId(1);
        }

        /*
        $stmt = $con->prepare(
            ""
        );

        $stmt->bind_param("sss", $tarefa->get, $lastname, $email);
        $stmt->execute();

        $stmt->close();
        */
        $con->close();
        $con->desconectar();
    }
}