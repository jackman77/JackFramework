<?php
namespace Jack\Controller;

use Jack\Model\Home;
use Jack\System\JackController;

class HomeController extends JackController
{
    public function index(){

        Home::getTable();
    }

}