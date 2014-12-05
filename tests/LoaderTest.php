<?php

namespace djfm\ftr\tests;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
	public function testLoadersAreFound()
	{
		$loader = new \djfm\ftr\Loader();
		$this->assertGreaterThan(0, count($loader->getLoaders()));
	}
}