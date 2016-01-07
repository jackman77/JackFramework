<?php
namespace Jack\Controller;

use Jack\Model\Home;
use Jack\System\Controller;

class HomeController extends Controller
{

    public function index()
    {
        $home = new Home();

        $home->whereNotIn('id',[4,3])->get();

        jj($home);
        return $this->view('home');

    }

    public function getIndex(){
        return 'im home index';
    }

    public function getMore(){

        return 'im get more';
    }


}