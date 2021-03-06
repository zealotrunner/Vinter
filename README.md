Vinter
====

Very INTEResting (and tricky) php template

[![Latest Stable Version](https://poser.pugx.org/vinter/vinter/v/stable.png)](https://packagist.org/packages/vinter/vinter)
[![Build Status](https://travis-ci.org/zealotrunner/Vinter.png?branch=master)](https://travis-ci.org/zealotrunner/Vinter)
[![Coverage Status](https://coveralls.io/repos/zealotrunner/Vinter/badge.png?branch=master)](https://coveralls.io/r/zealotrunner/Vinter?branch=master)
[![Dependencies Status](https://depending.in/zealotrunner/Yapo.png)](http://depending.in/zealotrunner/Yapo)
[![License](https://poser.pugx.org/vinter/vinter/license.png)](https://packagist.org/packages/vinter/vinter)


Usage
-----

```php

<?php

include('src/tags/html5.php');

echo $html['lang=en'](
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
            Vinter::each($comments, function($comment) {$_(
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
);
```

Test
----

Install [Composer](https://github.com/composer/composer)
```shell
cd vinter
curl -sS https://getcomposer.org/installer | php
php composer.phar install --dev
php composer.phar dumpautoload -o
```

Test
```shell
./vendor/bin/phpunit tests/VinterTest.php
```

