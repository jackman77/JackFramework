<?php
namespace Jack\System;

// modified from https://github.com/adamtomecek/Template

class Template{

    private $layout;

    public $vars = array();
    public function __construct($layout = NULL){

        if(!empty($layout)){
            $this->layout = $layout;
        }
        return $this;
    }
    public function getVars(){
        return $this->vars;
    }
    public function &__get($key){
        if (array_key_exists($key, $this->vars)) {
            return $this->vars[$key];
        }
    }

    public function __set($key, $val){
        $this->vars[$key] = $val;
    }

    private function compile($file){
        if(is_file($file)){
            $keys = array(
                '@if {%%}' => '<?php if (\1): ?>',
                '@elseif {%%}' => '<?php ; elseif (\1): ?>',
                '@for {%%}' => '<?php for (\1): ?>',
                '@foreach {%%}' => '<?php foreach (\1): ?>',
                '@while {%%}' => '<?php while (\1): ?>',
                '@endif' => '<?php endif; ?>',
                '@endforeach' => '<?php endforeach; ?>',
                '@endfor' => '<?php endfor; ?>',
                '@endwhile' => '<?php endwhile; ?>',
                '@else' => '<?php ; else: ?>',
                '{{$%% = %%}}' => '<?php $\1 = \2; ?>',
                '{{$%%++}}' => '<?php $\1++; ?>',
                '{{$%%--}}' => '<?php $\1--; ?>',
                '{{$%%}}' => '<?php echo $\1; ?>',
                '@php' => '<?php',
                '@endphp' => '?>',
                '@include {%%}' => '<?php $parent = (dirname(dirname(__FILE__))."/Views/"); include ($parent."\1.jack.php"); ?>'
            );

            foreach ($keys as $key => $val) {
                $patterns[] = '#' . str_replace('%%', '(.+)',
                        preg_quote($key, '#')) . '#U';
                $replace[] = $val;
            }
            $content = self::minify(preg_replace($patterns, $replace, file_get_contents($file)));

            return $content;

        }else{
            return ("Template tidak ada : '$file'.");
        }
    }
    public function setLayout($layout){
        $this->layout = $layout;
        return $this;
    }


    public function setup($layout){
        $this->setLayout($layout);
        return $this;
    }

    public function render(){

        if(!empty($this->layout)){
            if(is_file($this->layout)){
                $template = $this->compile($this->layout);
            }else{
                return ("Template tidak ada : '".$this->layout."'.");
            }
        }else{
            $template = $this->compile($this->file);
        }
        return $this->evaluate($template, $this->getVars());
    }

    public static function minify($content)
    {
        $search = array(
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s'
        );
        $replace = array(
            '>',
            '<',
            '\\1'
        );
        return preg_replace($search, $replace, $content);
    }

    private function evaluate($code, array $variables = NULL){
        if($variables != NULL){
            extract($variables);
        }
        return eval('?>' . $code);
    }
}