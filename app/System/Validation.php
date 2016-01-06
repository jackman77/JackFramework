<?php
namespace Jack\System;


class Validation
{

    public $array = [];
    public $error = [];
    public $confirmation = [];
    private $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    public function __set($name, $value)
    {
        $this->array[$name] = $value;
    }

    public function run()
    {

        foreach ($this->array as $key => $value) {
            $expl = explode('|', $value);
            foreach ($expl as $vl) {
                switch ($vl) {
                    case "required":
                        $this->required($key);
                        break;
                    case "number":
                        $this->number($key);
                        break;
                    case "email":
                        $this->email($key);
                        break;
                    case "confirmation":
                        $this->confirmation($key);
                        break;
                    case (preg_match('/unique:.*/', $vl) ? true : false):
                        $this->unique($key, $vl);
                        break;
                    case (preg_match('/min:.*/', $vl) ? true : false):
                        $this->min($key, $vl);
                        break;
                    case (preg_match('/max:.*/', $vl) ? true : false):
                        $this->max($key, $vl);
                        break;
                }
            }

        }
        jj($this->error);

    }

    public function required($key)
    {
        if ($this->request->$key == '') {
            $this->error[$key][] = "This $key is required.";
        }
    }

    public function number($key)
    {
        if (!is_numeric($this->request->$key)) {
            $this->error[$key][] = "This $key is must numeric.";
        }
    }

    public function email($key)
    {
        if (!filter_var($this->request->$key, FILTER_VALIDATE_EMAIL)) {
            $this->error[$key][] = "This $key is must valid email.";
        }
    }

    public function confirmation($key)
    {
        $conf = str_replace('_confirmation','',$key);
        if ($this->request->$key != $this->request->$conf){
            $this->error[$key][] = "This $key must same with $conf";
        }
    }

    public function min($key, $vl)
    {
        $min = explode(':', $vl);
        if (strlen($this->request->$key) < $min[1]) {
            $this->error[$key][] = "This $key minimal $min[1] characters.";
        }
    }

    public function max($key, $vl)
    {
        $max = explode(':', $vl);
        if (strlen($this->request->$key) > $max[1]) {
            $this->error[$key][] = "This $key maximal $max[1] characters.";
        }
    }

    public function unique($key,$vl){
        $min = explode(':', $vl);
        $expl = explode(',',$min[1]);

        $namespace = 'Jack\\Model\\'.ucfirst($expl[0]);

        $model = new $namespace;

        $model->where($expl[1],$this->request->$key)->get();

        if ($model->result()){
            $reqq = $this->request->$key;
            $this->error[$key][] = "This $reqq already taken.";
        }
    }
}