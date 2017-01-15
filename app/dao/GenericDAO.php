<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 1:36 AM
 */

namespace Dao;


abstract class GenericDAO {
    abstract protected function get($query, $params);
    abstract public function consultaPorId($id);
    abstract public function consultaTodos();
}