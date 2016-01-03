<?php
use Jack\System\Route as Route;



Route::get('', function(){

   echo 'welcome';
});


Route::error(function(){
   echo '404';
});

Route::dispatch();