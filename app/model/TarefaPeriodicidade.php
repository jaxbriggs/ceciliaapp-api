<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/21/2017
 * Time: 12:17 AM
 */

namespace Model;


class TarefaPeriodicidade
{
    private $id;
    private $diaSemana;
    private $diaMes;
    private $diaPartir;
    private $passo;

    /**
     * TarefaPeriodicidade constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDiaSemana()
    {
        return $this->diaSemana;
    }

    /**
     * @param mixed $diaSemana
     */
    public function setDiaSemana($diaSemana)
    {
        $this->diaSemana = $diaSemana;
    }

    /**
     * @return mixed
     */
    public function getDiaMes()
    {
        return $this->diaMes;
    }

    /**
     * @param mixed $diaMes
     */
    public function setDiaMes($diaMes)
    {
        $this->diaMes = $diaMes;
    }

    /**
     * @return mixed
     */
    public function getDiaPartir()
    {
        return $this->diaPartir;
    }

    /**
     * @param mixed $diaPartir
     */
    public function setDiaPartir($diaPartir)
    {
        $this->diaPartir = $diaPartir;
    }

    /**
     * @return mixed
     */
    public function getPasso()
    {
        return $this->passo;
    }

    /**
     * @param mixed $passo
     */
    public function setPasso($passo)
    {
        $this->passo = $passo;
    }

}