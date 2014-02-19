<?php

require_once(dirname(__FILE__) . '/bootstrap.php');

use \Vinter\Vinter as V;

class VinterTest extends PHPUnit_Framework_TestCase {

    public function test() {
        include(dirname(__FILE__) . '/../src/tags/html5.php');

        // tag
        $this->assertEquals(
            $div,
            '<div></div>'
        );

        // id, class, tree
        $this->assertEquals(
            $div(
                $p['#a']['.x']['.z'],
                $p['.y']
            ),
            '<div><p id="a" class="x z"></p><p class="y"></p></div>'
        );

        // each
        $sources = array('a', 'b', 'c');
        $row_class = 'z';
        $this->assertEquals(
            $table(
                V::each($sources, function($class) use ($row_class) {
                    echo $tr[".$row_class"][".$class"]();
                })
            ),
            '<table><tr class="z a"></tr><tr class="z b"></tr><tr class="z c"></tr></table>'
        );


        // each
        $this->assertEquals(
            V::each($sources, function($id) {$_(
                $div['#' . $id]($id),
                $div('test'),
                $div
            );}),
            '<div id="a">a</div><div>test</div><div></div><div id="b">b</div><div>test</div><div></div><div id="c">c</div><div>test</div><div></div>'
        );

    }

    public function testArrayAccess() {
        include(dirname(__FILE__) . '/../src/tags/html5.php');

        // offsetExists
        $this->assertEquals(
            isset($div['anything']),
            true
        );

        // offsetSet
        $div['anything'] = 'something';

        // offsetUnset
        unset($div['anything']);
    }

    public function testReal() {
        include(dirname(__FILE__) . '/../src/tags/html5.php');

        $page_title = 'title';
        $comment = new stdClass();
        $comment->content = 'Lorem ipsum';
        $comment->link = 'http://github.com/';
        $comment->user = '';
        $comment->datetime = '2014-02-14';
        $comment->time = 'Valentines day';
        $comments = array(
            $comment,
            $comment
        );
        $has_footer = true;

        $this->assertEquals(
            $html['lang=en'](
                    $head(
                        $title($page_title)
                    ),
                    $body(
                        $header($h1($page_title)),
                        $nav(
                            $ul(
                                $li              ($a['href=#']['.current']('Blog')),
                                $li              ($a['href=#']            ('Archives')),
                                $li              ($a['href=#']            ('Contact')),
                                $li['.subscribe']($a['href=#']            ('Subscribe via. RSS'))
                            )
                        ),

                        $section['#comments'](
                            $header($h3('Comments')),
                            V::each($comments, function($comment) {$_(
                                $article(
                                    $header(
                                        $a["href={$comment->link}"]($comment->user)
                                    ),
                                    ' on ',
                                    $time["datetime={$comment->datetime}"]($comment->time)
                                ),
                                $p($comment->content)
                            );})
                        )
                    ),

                    $has_footer ? $footer('&copy;Vinter') : ''
            ),
            '<html lang="en"><head><title>title</title></head><body><header><h1>title</h1></header><nav><ul><li><a href="#" class="current">Blog</a></li><li><a href="#">Archives</a></li><li><a href="#">Contact</a></li><li class="subscribe"><a href="#">Subscribe via. RSS</a></li></ul></nav><section id="comments"><header><h3>Comments</h3></header><article><header><a href="http://github.com/"></a></header> on <time datetime="2014-02-14">Valentines day</time></article><p>Lorem ipsum</p><article><header><a href="http://github.com/"></a></header> on <time datetime="2014-02-14">Valentines day</time></article><p>Lorem ipsum</p></section></body><footer>&copy;Vinter</footer></html>');
    }
}

