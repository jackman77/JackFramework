<?php
namespace Jack\System;

class Template
{

    private $result;
    private $var = [];


    public function __set($name, $value)
    {
        array_push($this->var, [$name => $value]);
    }


    public function load($file)
    {
        $viewdir = dirname(dirname(__FILE__)) . '/Views/';
        $this->viewdir = dirname(dirname(__FILE__)) . '/Views/';

        $files = file_get_contents($viewdir . $file . ".jack.php");
        $this->result = $files;

        return $this;
    }

    public function parseInclude()
    {
        if (strpos($this->result, '@include') !== false) {

            $viewdir = dirname(dirname(__FILE__)) . '/Views/';
            // ambil include
            preg_match_all('/@include.*\((.*)\)/', $this->result, $match);


            // masukan variable per include
            $x = 0;
            foreach ($match[1] as $incl) {
                // remove quotes
                $incl = str_replace('"', "", $incl);
                $incl = str_replace("'", "", $incl);


                // ambil include
                $file = file_get_contents($viewdir . $incl . ".jack.php");


                // replace dengan include
                $this->result = str_replace($match[0][$x], $file, $this->result);

                $x++;
            }

            // kembali ke parse
            return $this->parse();


        }
    }

    public function parse($incl = null)
    {
        // parse include jika ada
        if (strpos($this->result, '@include') !== false) {
            $this->parseInclude();
        }
        $keys = array(
            '@if(%%)' => '<?php if (\1): ?>',
            '@if%%(%%)' => '<?php if (\2): ?>',
            '@elseif(%%)' => '<?php ; elseif (\1): ?>',
            '@elseif%%(%%)' => '<?php ; elseif (\2): ?>',
            '@foreach(%%)' => '<?php foreach (\1): ?>',
            '@foreach%%(%%)' => '<?php foreach (\2): ?>',
            '@for(%%)' => '<?php for (\1): ?>',
            '@for%%(%%)' => '<?php for (\2): ?>',
            '@while(%%)' => '<?php while (\1): ?>',
            '@while%%(%%)' => '<?php while (\2): ?>',
            '@endif' => '<?php endif; ?>',
            '@endforeach' => '<?php endforeach; ?>',
            '@endfor' => '<?php endfor; ?>',
            '@endwhile' => '<?php endwhile; ?>',
            '@else' => '<?php ; else: ?>',
            '{{%%=%%}}' => '<?php \1 = \2; ?>',
            '{{%%++%%}}' => '<?php \1++; ?>',
            '{{%%--%%}}' => '<?php \1--; ?>',
            '{{%%}}' => '<?php echo \1; ?>',
            '@php' => '<?php',
            '@endphp' => '?>'
        );

        foreach ($keys as $key => $val) {
            $patterns[] = '#' . str_replace('%%', '(.+)',
                    preg_quote($key, '#')) . '#U';
            $replace[] = $val;
        }

        $this->result = preg_replace($patterns, $replace, $this->result);

        return $this->compile();

    }

    public function compile()
    {
        if ($this->var) {
            foreach ($this->var as $var) {
                extract($var);
            }
        }
        ob_start();
        eval("?>$this->result");
        $this->result = ob_get_contents();
        ob_end_clean();

        return $this;
    }

    public function result()
    {
        $this->parse();

        return $this->result;
    }
}