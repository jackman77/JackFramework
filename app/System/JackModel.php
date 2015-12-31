<?php
namespace Jack\System;

abstract class JackModel extends JackORM{

    protected $table;

    public function __construct($table)
    {
        $table = $this->table;

    }

    public static function getTable(){
        return $this->table;
    }

}