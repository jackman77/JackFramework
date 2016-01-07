<?php
use Jack\System\Route as Route;

Route::get('/home/', 'HomeController@index');


Route::error(function () {
    echo '404';
});

