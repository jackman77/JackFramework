<?php
use Jack\System\Route as Route;



Route::get('', function(){

   $a = [1 => 2, 2=>3, 3=>4, 5=>6];
   $a = json_encode($a);
   echo $a;
});

Route::get('/var/{var}', function($var){
   echo $var;
});

Route::get('/asd', 'HomeController@index');


Route::dispatch();