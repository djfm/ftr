<?php

namespace djfm\ftr\tests\fixtures;

use PHPUnit_Framework_TestCase;
use Exception;

class ExtraTestFunctionsAreCalledTest extends PHPUnit_Framework_TestCase
{
	private $wasChanged = false;
	private static $wasChangedStatic = false;

	public function setup()
	{
		$this->wasChanged = true;
	}

	public function tearDown()
	{
		static::$wasChangedStatic = true;
	}

    public function testSetup()
    {
    	$this->assertEquals(true, $this->wasChanged);
    }

    public function testTearDown()
    {
    	$this->assertEquals(true, static::$wasChangedStatic);
    }
}
