<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 1:36 AM
 */

namespace Dao;

use PDO;
use Dao\Conector;

abstract class GenericDAO {
    abstract public function consultaPorId($id);
    abstract public function consultaTodos();
    protected function get($query, $singular, $params = array())
    {
        $con = new Conector;
        $con->conectar();

        $sth = Conector::$conexao->prepare($query);
        $sth->execute($params);

        if($singular) {

            $sth->setFetchMode(PDO::FETCH_ASSOC);
            $red = $sth->fetch();
            if ($red) {
                return $red;
            }
        } else {

            $red = $sth->fetchAll(PDO::FETCH_ASSOC);

            if ($red) {
                return $red;
            }
        }

        $con->desconectar();

        return null;
    }
}