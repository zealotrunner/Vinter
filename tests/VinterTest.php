<?php

require_once(dirname(__FILE__) . '/bootstrap.php');

use \Vinter\Vinter as V;

class VinterTest extends PHPUnit_Framework_TestCase {

    public function test_v() {
		include(dirname(__FILE__) . '/../src/load.php');

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
					$tr[".$row_class"][".$class"]();
				})
			),
			'<table><tr class="z a"></tr><tr class="z b"></tr><tr class="z c"></tr></table>'
		);


		$this->assertEquals(
			V::each($sources, function($id) {$_(
				$div['#' . $id]($id),
				$div('test'),
				$div
			);}),
			'<div id="a">a</div><div>test</div><div></div><div id="b">b</div><div>test</div><div></div><div id="c">c</div><div>test</div><div></div>'
		);

    }
}

