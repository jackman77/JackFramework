<?php
namespace Jack\System;

use Jack\System\Template;


abstract class Controller
{
    private $template;

    public function view($name)
    {
        $template = new Template();
        $template->load($name);
        $this->template = $template;

        return $this;
    }

    public function with($array)
    {

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $this->template->$key = $value;
            }
        }

        return $this;

    }


    public function __toString()
    {
        return $this->template->result();
    }


}