<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 1:28 AM
 */

namespace Dao;

use PDO;

class UsuarioDAO extends GenericDAO
{
    /**
     * UsuarioDAO constructor.
     */
    public function __construct()
    {
    }

    public function consultaPorId($id)
    {
        return self::get('SELECT id, nome, login FROM usuario WHERE id = ?', true, array($id));
    }

    public function consultaPorLogin($login)
    {
        return self::get('SELECT id, nome, login, senha FROM usuario WHERE login = ?', true, array($login));
    }

    public function consultaTodos()
    {
        return $this->get('SELECT id, nome, login FROM usuario', false);
    }

    protected function get($query, $singular, $params = array())
    {
        $con = new Conector;
        $con->conectar();

        $sth = Conector::$conexao->prepare($query);
        $sth->execute($params);

        if($singular) {

            $sth->setFetchMode(PDO::FETCH_CLASS, "Model\\Usuario");
            $red = $sth->fetch();
            if ($red) {
                return $red;
            }
        } else {

            $red = $sth->fetchAll(PDO::FETCH_CLASS, "Model\\Usuario");

            if ($red) {
                return $red;
            }
        }

        $con->desconectar();

        return null;
    }

}