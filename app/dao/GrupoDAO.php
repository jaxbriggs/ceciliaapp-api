<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/22/2017
 * Time: 9:20 PM
 */

namespace Dao;


class GrupoDAO extends GenericDAO
{


    /**
     * GrupoDAO constructor.
     */
    public function __construct()
    {
    }

    public function consultaPorId($id)
    {
        //NOT USED
    }

    public function consultaTodos()
    {
        return parent::get("SELECT id, nome, dt_cadastro FROM grupo", false);
    }
}