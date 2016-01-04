<?php
namespace Jack\System;


abstract class Model
{

    protected $conn;
    protected $table;
    private $lastID;

    //variabel hasil
    protected $result;

    // variabel query sql
    private $query;
    private $select = [];
    private $where = [];
    private $join = [];

    //binding values
    private $bind = [];

    // konstruk buat koneksi ke mysql
    public function __construct()
    {
        try {
            $this->conn = new \PDO("mysql:host=" . DB_HOST . ";
                                  dbname=" . DATABASE . ";
                                  charset=" . DB_CHARSET . "",
                DB_USERNAME,
                DB_PASSWORD,
                array(\PDO::MYSQL_ATTR_FOUND_ROWS => true));
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(\PDO::ATTR_PERSISTENT, true);
        } catch (\PDOException $e) {
            $this->$error = $e->getMessage();
        }

    }

    // magic method set variabel dari result
    public function __set($name, $value)
    {
        if (isset($this->result[$name])) {
            $this->result[$name] = $value;
        }

    }

    // magic method get variable dari result
    public function __get($name)
    {
        if (isset($this->result[$name])) {
            return $this->result[$name];
        }
    }

    // eksekusi query gabungan
    public function get(){

        $query = (!empty($this->select)) ? $this->select : "SELECT * FROM $this->table";
        // gabung select
        foreach ($this->select as $select){

        }
        // gabung where
        foreach ($this->where as $where){
            $query .= $where;
        }

        $sql = $this->conn->prepare($query);

        foreach ($this->bind as $key => &$value){
            $sql->bindParam("$key", $value);
        }

        $sql->execute();

        $this->result = $sql->fetchAll(\PDO::FETCH_ASSOC);
        return $this->result;

    }


    public function find($id)
    {
        $sql = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
        $sql->bindParam(':id', $id);
        $sql->execute();

        $this->result = $sql->fetch(\PDO::FETCH_ASSOC);
        return $this->result;
    }

    // where function
    public function where($column, $value, $operator = null)
    {
        // setting operator jika null atau tidak
        (!isset($operator)) ?: $value = $operator;$operator = "=";

        // cek where empty
        if (empty($this->where)){
            $this->where = [" WHERE $column $operator :0$value"];
            $this->bind = [":0$value" => $value];
        }else{
            $this->where = [" AND WHERE $column $operator :0$value"];
            $this->bind = [":0$value" => $value];
        }
        return $this;
    }



    // insert dengan model tabel
    public function insert($data)
    {
        if (is_array($data)) {
            $column = implode(',', array_keys($data));
            $values = ':' . implode(',:', array_keys($data));

            $sql = $this->conn->prepare("INSERT INTO " . $this->table . " ( $column ) VALUES ( $values )");
            foreach ($data as $key => &$value) {
                $sql->bindParam(":$key", $value);
            }
            $sql->execute();

            $this->lastID = $this->conn->lastInsertId();
            $this->find($this->lastID);

        }


    }


}