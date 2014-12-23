<?php

namespace djfm\ftr\tests\fixtures;

use PHPUnit_Framework_TestCase;
use Exception;

class DyingTest extends PHPUnit_Framework_TestCase
{
	public function testDying()
	{
		die("dead!");
	}

	public function testOK()
	{
		
	}
}
