<?php

namespace Jack\System;

class Route
{


    static $uri = [];
    static $method = [];
    static $action = [];

    // masukan list route ke list
    public static function __callStatic($method, $action)
    {

        array_push(static::$method,strtoupper($method));
        array_push(static::$uri,$action[0]);
        array_push(static::$action,$action[1]);
    }


    // dispatch dari url yg masuk
    public static function dispatch()
    {

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // bersihkan uri
        $uri = rtrim(preg_replace('~/+~', '/', $uri),'/');
        var_dump(static::$uri);

    }


}