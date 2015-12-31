<?php
namespace Jack\System;

class JackRoute{

    public static $halts = false;
    public static $routes = array();
    public static $methods = array();
    public static $callbacks = array();
    public static $patterns = array(
        ':var' => '[^/]+',
        ':num' => '[0-9]+'
    );
    public static $error_callback;

    public static function __callstatic($method, $params) {
        $uri = dirname($_SERVER['PHP_SELF']).$params[0];
        $callback = $params[1];
        array_push(self::$routes, $uri);
        array_push(self::$methods, strtoupper($method));
        array_push(self::$callbacks, $callback);
    }
    public static function error($callback) {
        self::$error_callback = $callback;
    }
    public static function haltOnMatch($flag = true) {
        self::$halts = $flag;
    }
    public static function dispatch(){
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $searches = array_keys(static::$patterns);
        $replaces = array_values(static::$patterns);
        $found_route = false;
        self::$routes = str_replace('//', '/', self::$routes);
        if (in_array($uri, self::$routes)) {
            $route_pos = array_keys(self::$routes, $uri);
            foreach ($route_pos as $route) {
                if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY') {
                    $found_route = true;
                    if (!is_object(self::$callbacks[$route])) {
                        $segments = explode('@',self::$callbacks[$route]);
                        $segments[0] =  str_replace('/','\\',$segments[0]);
                        $namespace = "Jack\\Controller\\".$segments[0];
                        $controller = new $namespace();
                        $controller->$segments[1]();
                        if (self::$halts) return;
                    } else {
                        call_user_func(self::$callbacks[$route]);
                        if (self::$halts) return;
                    }
                }
            }
        } else {
            $pos = 0;
            foreach (self::$routes as $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }
                if (preg_match('#^' . $route . '$#', $uri, $matched)) {
                    if (self::$methods[$pos] == $method) {
                        $found_route = true;
                        array_shift($matched);
                        if (!is_object(self::$callbacks[$pos])) {
                            $segments = explode('@',self::$callbacks[$pos]);
                            $segments[0] =  str_replace('/','\\',$segments[0]);
                            $namespace = "Jack\\Controller\\".$segments[0];
                            $controller = new $namespace();
                            $controller->$segments[1]();
                            if (self::$halts) return;
                        } else {
                            call_user_func_array(self::$callbacks[$pos], $matched);
                            if (self::$halts) return;
                        }
                    }
                }
                $pos++;
            }
        }
        if ($found_route == false) {
            if (!self::$error_callback) {
                self::$error_callback = function() {
                    header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
                    echo '404';
                };
            }
            call_user_func(self::$error_callback);
        }
    }

}