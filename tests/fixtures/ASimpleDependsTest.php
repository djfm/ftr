<?php

namespace djfm\ftr\tests\fixtures;

class ASimpleDependsTest
{
    public function testA()
    {

    }

    /**
	 * @depends testA
	 */
    public function testB($a)
    {

    }

    /**
	 * @depends testA
	 * @depends testB
	 */
    public function testC($a, $b)
    {

    }

    /**
	 * @depends testB
	 * @depends testA
	 */
    public function testD($b, $a)
    {

    }
}
