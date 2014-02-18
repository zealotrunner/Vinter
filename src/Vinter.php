<?php

namespace Vinter;

class Vinter implements \ArrayAccess {

    private static $instances = array();

    private $tag;
    private $attributes = array();

    public static function i($name) {
        $tags = is_array($name)
            ? $name
            : array($name);

        $result = array();
        foreach ($tags as $t) {
            if (empty(self::$instances[$t])) {
                $i = new self($t);

                self::$instances[$t] = $i;
            }

            $result[$t] = self::$instances[$t];
        }

        return is_array($name)
            ? $result
            : array_pop($result); // the only element
    }

    private function __construct($tag) {
        $this->tag = $tag;
    }

    private static function loaded_tags() {
        return self::$instances;
    }

    public static function _(/*arguments*/) {
        $r = '';
        foreach (func_get_args() as $a) {
            $r .= ($a instanceof Vinter)
                ? $a()
                : $a;
        }

        echo $r;
        return $r;
    }

    public function offsetGet($attribute_string) {
        if ($this->attributes) {
            $s = $this;
        } else {
            // copy a new Vinter
            $s = new self($this->tag);
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

        return $r;
    }

    public static function each($sources, $callback) {
        $i = 0;
        $result = '';

        $callback = self::injected($callback);
        foreach ($sources as $k => $s) {
            $r = $callback($s, $k, $i);

            $result .= $r;
            $i++;
        }

        return $result;
    }

    // public static function _if($condition, $callback) {
    //     return $condition
    //         ? call_user_func(self::injected($callback))
    //         : '';
    // }

    private function parse($attribute_string) {
        // #id.class
        // attr=value
        preg_match_all('/[#\.][\w-]+|[\w-]+=[^\s]+/', $attribute_string, $matches);
        list($segments) = $matches;

        return array_reduce($segments, function($r, $segment) {
            // expand . and #
            $segment = preg_replace('/^#/', 'id=', $segment);
            $segment = preg_replace('/^\./', 'class=', $segment);

            list($name, $value) = explode('=', $segment);

            $r[$name] = $value;

            return $r;
        }, array());
    }

    private static function injected($closure) {
        $func = new \ReflectionFunction($closure);
        $filename = $func->getFileName();
        $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line;

        $lines = '<?php ' . implode('', array_slice(file($filename), $start_line, $length));

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

        $inner_uses = $func->getStaticVariables();

        $_ = function(/*arguments*/) {
            return call_user_func_array('\Vinter\Vinter::_', func_get_args());
        };

        $loaded_tags = self::loaded_tags();

        // all injected variables, comma separated
        $injected_uses = '$_, ' . implode(', ', array_map(function($t) {
            return '$' . $t;
        }, array_merge(array_keys($loaded_tags), array_keys($inner_uses))));

        // todo: variable name conflict
        return call_user_func(function() use ($inner_uses, $loaded_tags, $args, $injected_uses, $fbody, $_) {

            extract($inner_uses);
            extract($loaded_tags);

            eval("\$decorated =
                function ($args) use ($injected_uses) {
                    ob_start();
                    $fbody;
                    return ob_get_clean();
                };
            ");

            return $decorated;
        });
    }
}
