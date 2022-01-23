<?php

namespace Rex\Ext;

class Pdo
{
    public $pdo=null;

    public $stmt = null;

    public $log = array();

    public $dsn;
    public $user;
    public $password;
    public $options;

    public function __construct($dsn,$user='',$password='',$options = array())
    {
        $options += array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->options = $options;

        $this->connect(); //连接
    }

    private function connect(){
        $this->pdo = new \PDO($this->dsn, $this->user, $this->password, $this->options);

        return $this->pdo;
    }

    public function close(){
        $this->pdo = null;
    }

    public function free(){
        $this->stmt = null;
    }

    /*
     * @param string $sql
     * return
     */
    public function query($sql, $data = array())
    {
        $this->log[] = array('time' => date('Y-m-d H:i:s'), 'sql' => $sql, 'data' => $data);
        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute($data);
        return $this->stmt;
    }

    /*
     * insert
     * @param string $table
     * @param array $data
     * return
     */
    public function insert($table,$data)
    {
        $keys = [];
        $marks = [];
        $values = [];
        foreach ($data as $key=>$val){
            is_array($val) && ($val = json_encode($val, JSON_UNESCAPED_UNICODE));
            $keys[] = "`{$key}`";
            $marks[] = "?";
            $values[] = $val;
        }
        $keys = implode(',',$keys);
        $marks = implode(',',$marks);
        $sql = "INSERT INTO {$table} ({$keys}) VALUES ({$marks})";
        $this->sql($sql,$values);
    }

    public function sql($sql,$data){
        $this->query($sql);
        $tags = explode(' ',$sql,2);
        switch (strtoupper($tags[0])){
            case "SELECT":
                $result = $this->fetchAll();
                break;
            case "INSERT":
                $result = $this->lastInsertId();
            case "UPDATE":
            case "DELETE":
            default:
                $result = $this->stmt;
        }
        return $result;
    }

    public function fetchAll($style = PDO::FETCH_ASSOC)
    {
        $result = $this->stmt->fetchAll($style);
        $this->free();
        return $result;
    }

    public function lastInsertId($name = null)
    {
        $last = $this->pdo->lastInsertId($name);
        if (false === $last) {
            return false;
        } else if ('0' === $last) {
            return true;
        } else {
            return intval($last);
        }
    }




}