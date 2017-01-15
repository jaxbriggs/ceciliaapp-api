<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/6/2017
 * Time: 1:08 AM
 */

namespace Dao;

use PDO;

class Conector
{
    public static $conexao;

    private $host;
    private $dbname;
    private $username;
    private $password;

    private $isProduction = true;

    /**
     * Conector constructor.
     * @param $host
     * @param $dbname
     * @param $username
     * @param $password
     */
    public function __construct()
    {
        $this->host = ($this->isProduction ? "ec2-54-163-236-33.compute-1.amazonaws.com" : "localhost");
        $this->dbname = ($this->isProduction ? "d6f7vpr4ncbojk" : "id464400_cecilia_db");
        $this->username = ($this->isProduction ? "mxabczusrtxttw" : "root");
        $this->password = ($this->isProduction ? "966de4fcb802f387f69f624066e5ce64b83b4025cc4105af918a59d03c29a298" : "");
    }

    public function conectar(){
        //self::$conexao = new PDO('mysql:host='.$this->host.';dbname='.$this->dbname.';charset=utf8', $this->username, $this->password);
        self::$conexao = new PDO("pgsql:dbname=$this->dbname;host=$this->host", $this->username, $this->password);
        self::$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$conexao->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function desconectar(){
        self::$conexao = NULL;
    }

}