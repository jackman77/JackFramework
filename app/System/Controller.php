<?php
namespace Jack\System;

class Controller
{
    private $template;
    public $request;
    public $err = [];


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

    public function validate($array = null)
    {

        $validate = new Validation();

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $validate->$key = strtolower($value);
            }
        }

        $validate->run();
        $this->request = $validate->request;
        if ($validate->error) {
            $this->err = $validate->error;
        }

        return;

    }


    public function __toString()
    {
        return $this->template->result();
    }


}