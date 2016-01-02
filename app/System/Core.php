<?php

namespace Jack\System;

class Core{

    public function __call($name, $arguments)
    {
        require __DIR__.'/../Routes.php';
    }

}