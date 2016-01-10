<?php

function object($array){
    if (is_array($array)){
        return json_decode(json_encode($array), FALSE);
    }
    return;
}

function jj($dump){
    return var_dump($dump);
}