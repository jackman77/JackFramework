<?php

namespace Jack\System;

// inspired from https://github.com/noahbuscher/Macaw

class Route
{

    static $uri = [];
    static $method = [];
    static $action = [];
    static $error;
    static $result;

    // masukan list route ke list
    public static function __callStatic($method, $action)
    {
        array_push(static::$method, strtoupper($method));
        array_push(static::$uri, $action[0]);
        array_push(static::$action, $action[1]);
    }


    // fungsi 404

    public static function error($err)
    {
        static::$error = $err;
    }

    public static function result(){
        return self::$result;
    }

    // dispatch dari url yg masuk
    public static function dispatch()
    {


        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // bersihkan uri
        $uri = rtrim(preg_replace('~/+~', '/', $uri), '/');
        (!empty($uri)) ?: $uri = '/';

        // cek list routes dgn url yang masuk

        if (in_array($uri, static::$uri)) {

            $no = array_keys(static::$uri, $uri);
            if (static::$method[$no[0]] == $method) {
                // return object / function
                if (is_object(static::$action[$no[0]])) {
                    static::$result =  call_user_func(static::$action[$no[0]]);
                    return;
                }
                // return controller
                $expl = explode('@', static::$action[$no[0]]);
                $namespace = "Jack\\Controller\\" . $expl[0];
                $controller = new $namespace();

                static::$result = $controller->$expl[1]();
                return;

            }
        }

        $x = 0;
        // looping list route
        foreach (static::$uri as $route) {
            if (static::$method[$x] == $method) {

                // replace { }
                if (strpos($route, '{') !== false) {
                    $route = preg_replace('/{(.*?)}/', '([^/]+)', $route);
                }

                // cari pake regex
                if (preg_match('#^' . $route . '$#', $uri, $var)) {

                    // hapus array no 0
                    array_shift($var);

                    if (is_object(static::$action[$x])) {
                        // call object + insert variable
                        static::$result = call_user_func_array(static::$action[$x], $var);
                        return;
                    }
                    // call controller + insert variable
                    $expl = explode('@', static::$action[$x]);
                    $namespace = "Jack\\Controller\\" . $expl[0];
                    $controller = new $namespace();

                    static::$result = call_user_func_array([$controller, $expl[1]], $var);
                    return;
                }
            }
            $x++;
        }

        // call 404
        return call_user_func(static::$error);

    }
}