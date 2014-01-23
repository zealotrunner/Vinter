<?php

namespace Vinter;

class Vinter implements \ArrayAccess {

    private static $instances = array();

    private $tag;
    private $echo;
    private $attributes = array();

    public static function i($name, $echo=false) {
        $tags = is_array($name)
            ? $name
            : array($name);

        if (empty(self::$instances)) {
            self::$instances = array(true => array(), false => array());
        }

        $result = array();
        foreach ($tags as $t) {
            if (empty(self::$instances[$echo][$t])) {
                $echoing = new self($t, true);
                $returning = new self($t, false);

                self::$instances[true][$t] = $echoing;
                self::$instances[false][$t] = $returning;
            }

            $result[$t] = self::$instances[$echo][$t];
        }

        return is_array($name) 
            ? $result
            : array_pop($result); // the only element
    }

    private function __construct($tag, $echo=false) {
        $this->tag = $tag;
        $this->echo = $echo;
    }

    private static function tags($echo=false) {
        return self::$instances[$echo];
    }

    public static function _(/*arguments*/) {
        $r = '';
        foreach (func_get_args() as $a) {
            $r .= ($a instanceof Vinter)
                ? $a()
                : $a;
        }

        return $r;
    }

    public function offsetGet($attribute_string) {
        if ($this->attributes) {
            $s = $this;
        } else {
            // copy a new Vinter
            $s = new self($this->tag, $this->echo);
        }

        foreach ($this->parse($attribute_string) as $name => $value) {
            if ($name == 'class') {
                $s->attributes['class'] = !empty($this->attributes['class'])
                    ? $this->attributes['class']. ' ' . $value
                    : $value;
            } else {
                $s->attributes[$name] = $value;
            }
        }

        return $s;
    }

    public function offsetExists($t) {return true;}

    public function offsetSet($name, $value) {return null;}

    public function offsetUnset($name) {return null;}

    public function __toString() {
        // __invoke
        return $this();
    }

    public function __invoke(/*arguments*/) {
        // supports $tag => $tag()
        $args = array_map(function($a) {
            return ($a instanceof Vinter)
                ? $a()
                : $a;
        }, func_get_args());

        $attr_string = $this->attributes
            ? ' ' . implode(' ', array_map(function($name, $value) {
                return $name
                    ? "$name=\"$value\""
                    : '';
            }, array_keys($this->attributes), array_values($this->attributes)))
            : '';


        $r = "<{$this->tag}$attr_string>" . implode('', $args) . "</{$this->tag}>";

        if ($this->echo) {
            echo $r;
        }

        return $r;
    }

    private function parse($attribute_string) {
        // #id.class
        // attr=value
        preg_match_all('/[#\.]\w+|\w+=\w+/', $attribute_string, $matches);
        list($segments) = ($matches);

        return array_reduce($segments, function($r, $segment) {
            if (!$segment) {
                $segment = $attribute_string;
            }

            $segment = str_replace('#', 'id=', $segment);
            $segment = str_replace('.', 'class=', $segment);

            list($name, $value) = explode('=', $segment);

            $r[$name] = $value;

            return $r;
        }, array());
    }

    public function each($sources, $callback) {
        $i = 0;
        $result = '';

        $callback = self::copyClosureUse($callback);
        foreach ($sources as $k => $s) {
            $r = $callback($s, $k, $i);

            //todo
            if (is_array($r)) {
                foreach ($r as $l) {
                    if ($l instanceof Vinter) {
                        $result .= $l();
                    } else {
                        $result .= $l;
                    }
                }
            } else {
                $result .= $r;
            }
            $i++;
        }

        return $result;
    }

    private static function copyClosureUse($closure) {
        $func = new \ReflectionFunction($closure);
        $filename = $func->getFileName();
        $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line;

        $source = file($filename);
        $lines = implode("", array_slice($source, $start_line, $length));

        $lines = "<?php " . $lines;

        $args = $uses = $fbody = '';

        Token::i($lines)
        ->to(T_FUNCTION)
        ->collectBetween('(', ')', function($collects) use (&$args) {
            $args =  implode('', array_map(function($c) {
                return $c['value'];
            }, $collects));
        })

        ->tryto(T_USE)
        ->collectBetween('(', ')', function($collects) use (&$uses) {
            $uses = implode('', array_map(function($c) {
                return $c['value'];
            }, $collects));

        })
        ->collectBetween('{', '}', function($collects) use (&$fbody) {
            $fbody = implode('', array_map(function($c) {
                return $c['value'];
            }, $collects));
        });

        $original_uses = $func->getStaticVariables();

        $tags = self::tags($echo=true);
        $comma_uses = implode(', ', array_map(function($t) {
            return '$' . $t;
        }, array_keys($tags)));

        if ($original_uses) {
            $comma_uses .= ', ' . implode(', ', array_map(function($t) {
                return '$' . $t;
            }, array_keys($original_uses)));
        }

        $_ = function(/*arguments*/) {
            return call_user_func_array('\Vinter\Vinter::_', func_get_args());
        };
        $comma_uses .= ', $_';

        // todo: variable name conflict
        return call_user_func(function() use ($original_uses, $tags, $args, $comma_uses, $fbody, $_) {

            extract($original_uses);
            extract($tags);

            eval("\$decorated =
                function ($args) use ($comma_uses) {
                    ob_start();
                    $fbody;
                    return ob_get_clean();
                };
            ");

            return $decorated;
        });
    }
}
