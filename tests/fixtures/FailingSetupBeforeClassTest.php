<?php

namespace djfm\ftr\tests\fixtures;

use PHPUnit_Framework_TestCase;
use Exception;

class FailingSetupBeforeClassTest extends PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		throw new Exception("Could not setup test - on purpose.");
	}

	public function testNeverTested()
	{

	}

	public function testNeverTestedEither()
	{

	}
}
