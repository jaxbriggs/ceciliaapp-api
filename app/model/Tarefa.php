<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/20/2017
 * Time: 11:58 PM
 */

namespace Model;

class Tarefa
{

    private $id;
    private $titulo;
    private $periodicidade;
    private $grupo;
    private $responsavel;
    private $dtCadastro;

    /**
     * Tarefa constructor.
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
    public function getTitulo()
    {
        return $this->titulo;
    }

    /**
     * @param mixed $titulo
     */
    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    /**
     * @return mixed
     */
    public function getPeriodicidade()
    {
        return $this->periodicidade;
    }

    /**
     * @param mixed $periodicidade
     */
    public function setPeriodicidade($periodicidade)
    {
        $this->periodicidade = $periodicidade;
    }

    /**
     * @return mixed
     */
    public function getGrupo()
    {
        return $this->grupo;
    }

    /**
     * @param mixed $grupo
     */
    public function setGrupo($grupo)
    {
        $this->grupo = $grupo;
    }

    /**
     * @return mixed
     */
    public function getResponsavel()
    {
        return $this->responsavel;
    }

    /**
     * @param mixed $responsavel
     */
    public function setResponsavel($responsavel)
    {
        $this->responsavel = $responsavel;
    }

    /**
     * @return mixed
     */
    public function getDtCadastro()
    {
        return $this->dtCadastro;
    }

    /**
     * @param mixed $dtCadastro
     */
    public function setDtCadastro($dtCadastro)
    {
        $this->dtCadastro = $dtCadastro;
    }


}