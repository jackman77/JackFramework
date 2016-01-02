<?php

namespace Jack\System;

class Route
{


    static $uri = [];
    static $method = [];
    static $action = [];
    static $var = [];

    // masukan list route ke list
    public static function __callStatic($method, $action)
    {
        array_push(static::$method, strtoupper($method));
        array_push(static::$uri, $action[0]);
        array_push(static::$action, $action[1]);
    }


    // dispatch dari url yg masuk
    public static function dispatch()
    {

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // bersihkan uri
        $uri = rtrim(preg_replace('~/+~', '/', $uri), '/');

        // cek list routes dgn url yang masuk

        if (in_array($uri, static::$uri)) {

            $no = array_keys(static::$uri, $uri);

            if (static::$method[$no[0]] == $method) {
                // return object / function
                if (is_object(static::$action[$no[0]])) {
                    return call_user_func(static::$action[$no[0]]);
                }
                // return controller

                $expl = explode('@', static::$action[$no[0]]);
                $namespace = "Jack\\Controller\\" . $expl[0];
                $controller = new $namespace();
                return $controller->$expl[1]();

            }


        }

        $x = 0;
        // looping list route
        foreach (static::$uri as $route) {
            // replace { }
            if (strpos($route, '{') !== false) {
                $route = preg_replace('/{(.*?)}/', '[^/]+', $route);
            }

            // cari pake regex
            if (preg_match('#^' . $route . '$#', $uri, $match)){

                var_dump($match);
            }


            $x++;
        }




    }
}