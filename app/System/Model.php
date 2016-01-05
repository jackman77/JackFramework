<?php
namespace Jack\System;


abstract class Model
{

    protected $conn;
    protected $table;
    private $lastID;
    private $fetchmode = 0;
    private $counter = 0;

    //variabel hasil
    protected $result;

    // variabel query sql
    private $query;
    private $select = [];
    private $where = [];
    private $join = [];
    private $orderBy = [];
    private $limit;

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

    public function find($id)
    {
        $sql = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
        $sql->bindParam(':id', $id);
        $sql->execute();

        $this->result = $sql->fetch(\PDO::FETCH_ASSOC);

        return $this->result;
    }

    public function all()
    {
        $this->where = null;
        $this->limit = null;

        return $this->get();
    }

    public function first()
    {

        if (!empty($this->where) && !empty($this->join)) {
            $this->fetchmode = 1;
        } else {
            $this->fetchmode = 1;

            return $this->get();
        }

        return $this;
    }

    // eksekusi query gabungan
    public function get()
    {


        if (empty($this->select)) {
            $query = "SELECT * FROM $this->table";
        } else {
            $query = "SELECT " . implode(',', $this->select) . " FROM $this->table";
        }

        if ($this->join) {
            foreach ($this->join as $join) {
                $query .= $join;
            }
        }

        if ($this->where) {
            foreach ($this->where as $where) {
                $query .= $where;
            }
        }

        if ($this->orderBy) {
            foreach ($this->orderBy as $orderBy) {
                $query .= $orderBy;
            }
        }


        if (!empty($this->limit)) {
            $query .= " LIMIT $this->limit";
        }


        $sql = $this->conn->prepare($query);

        foreach ($this->bind as $key => &$value) {
            $sql->bindParam("$key", $value);
        }

        $sql->execute();
        if ($this->fetchmode) {
            $this->result = $sql->fetch(\PDO::FETCH_ASSOC);
        } else {
            $this->result = $sql->fetchAll(\PDO::FETCH_ASSOC);
        }


        $this->query = $query;

        return $this->result;

    }

    public function select($field)
    {
        if (is_array($field)) {
            foreach ($field as $fl) {
                array_push($this->select, $fl);
            }
        }

        return $this;
    }


    public function join($column, $field1, $field2, $operator = null)
    {
        if (is_null($operator)) {
            $operator = "=";
        } else {
            $temp = $operator;
            $operator = $field2;
            $field2 = $temp;
        }
        array_push($this->join, " INNER JOIN $column ON $field1 $operator $field2");

        return $this;

    }

    // where function
    public function where($column, $value, $operator = null)
    {
        // setting operator jika null atau tidak
        if (is_null($operator)) {
            $operator = "=";
        } else {
            $temp = $operator;
            $operator = $value;
            $value = $temp;
        }


        $coll = preg_replace('/[^A-Za-z0-9\-]/', '', $value);

        // cek where empty
        if (empty($this->where)) {
            array_push($this->where, " WHERE $column $operator :$this->counter$coll");
            $this->bind[":$this->counter$coll"] = $value;
        } else {
            array_push($this->where, " AND $column $operator :$this->counter$coll");
            $this->bind[":$this->counter$coll"] = $value;
        }
        $this->counter++;

        return $this;
    }

    public function orderBy($column, $type)
    {

        if (strtoupper($type) == 'ASC' || strtoupper($type) == 'DESC') {
            if (empty($this->orderBy)) {
                array_push($this->orderBy, " ORDER BY $column $type");
            } else {
                array_push($this->orderBy, " , $column $type");
            }
            $this->counter++;
        }

        return $this;

    }

    public function limit($int)
    {
        if (is_int($int)) {
            $this->limit = $int;
        }

        return $this;
    }


    // insert dengan model tabel
    public function insert($data)
    {
        if (is_array($data)) {
            $column = implode(',', array_keys($data));
            $values = ':' . implode(',:', array_keys($data));

            $sql = $this->conn->prepare("INSERT INTO $this->table ( $column ) VALUES ( $values )");
            foreach ($data as $key => &$value) {
                $sql->bindParam(":$key", $value);
            }
            $sql->execute();

            $this->lastID = $this->conn->lastInsertId();
            $this->find($this->lastID);

        }


    }

    public function save()
    {
        if ($this->result) {
            $query = "UPDATE $this->table SET ";
            $x = 0;
            foreach ($this->result as $key => $value) {

                if ($x == count($this->result) - 1) {
                    $query .= "$key = :$this->counter$value ";
                    $this->bind[":$this->counter$value"] = $value;
                } elseif (strtolower($key) !== 'id') {

                    $query .= "$key = :$this->counter$value, ";
                    $this->bind[":$this->counter$value"] = $value;
                }
                $this->counter++;
                $x++;

            }
            if ($this->where) {
                foreach ($this->where as $where) {
                    $query .= $where;
                }
            } else {
                $query .= "WHERE id = $this->id";
            }
            $sql = $this->conn->prepare($query);

            foreach ($this->bind as $key => &$value) {
                $sql->bindParam("$key", $value);
            }

            $sql->execute();
            $this->find($this->id);

        }

    }

    public function delete($id = null){

        if ($this->result && $id == null) {
            $id = $this->id;
            $query = "DELETE FROM $this->table WHERE id = :id";
            $sql = $this->conn->prepare($query);
            $sql->bindParam(':id',$id);
            $sql->execute();
            return $this;
        }

        if (is_int($id)){
            $query = "DELETE FROM $this->table WHERE id = :id";
            $sql = $this->conn->prepare($query);
            $sql->bindParam(':id',$id);
            $sql->execute();
            return $this;
        }

        if ($this->where){
            $query = "DELETE FROM $this->table";
            foreach ($this->where as $where) {
                $query .= $where;
            }
            $sql = $this->conn->prepare($query);
            foreach ($this->bind as $key => &$value) {
                $sql->bindParam("$key", $value);
            }
            $sql->execute();
            return $this;


        }


    }


}