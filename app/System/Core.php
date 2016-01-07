<?php

namespace Jack\System;

class Core
{

    public function __construct()
    {

        $route = new Route();

        require_once "/../Routes.php";

        $route->dispatch();
        $render = $route->result();

        if (is_array($render)) {
            echo json_encode($render);
        } else {
            echo($render);
        }


    }


}