<?php
namespace Jack\System;
use Jack\System\JackTemplate;

abstract class JackController{

    private $template;

    public function __construct()
    {
        $this->template = new JackTemplate();
    }

    public function view($view){

        $this->template->title = "Variable example";
        $this->template->array = array(
            '1' => "First array item",
            '2' => "Second array item",
            'n' => "N-th array item",
        );

        $parent = dirname(dirname(__FILE__));

        $this->template->setLayout($parent.'//Views//'.$view.'.jack.php');

        return $this->template->render();
    }

    public function with($array){

        $this->template->array = $array;

    }



}