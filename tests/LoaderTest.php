<?php

namespace djfm\ftr\tests;

use djfm\ftr\Loader\Loader;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
	public function testLoadersAreFound()
	{
		$loader = new Loader();
		$this->assertGreaterThan(0, count($loader->getLoaders()));
	}
}