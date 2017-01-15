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

    private $isProduction = false;

    /**
     * Conector constructor.
     * @param $host
     * @param $dbname
     * @param $username
     * @param $password
     */
    public function __construct()
    {
        $this->host = ($this->isProduction ? "" : "localhost");
        $this->dbname = ($this->isProduction ? "id464400_cecilia_db" : "id464400_cecilia_db");
        $this->username = ($this->isProduction ? "" : "root");
        $this->password = ($this->isProduction ? "" : "");
    }

    public function conectar(){
        self::$conexao = new PDO('mysql:host='.$this->host.';dbname='.$this->dbname.';charset=utf8', $this->username, $this->password);
        self::$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$conexao->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function desconectar(){
        self::$conexao = NULL;
    }

}