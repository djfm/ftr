<?php

namespace djfm\ftr\tests\fixtures;

use PHPUnit_Framework_TestCase;
use Exception;

class ADependsTest extends PHPUnit_Framework_TestCase
{
    public function testA()
    {
        return 42;
    }

    /**
	 * @depends testA
	 */
    public function testB($a)
    {
        $this->assertEquals(42, $a);
    }

    public function testC()
    {
        throw new Exception("Na na na.");
    }

    /**
	 * @depends testC
	 */
    public function testD($c)
    {

    }
}
