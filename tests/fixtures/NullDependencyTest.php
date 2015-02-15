<?php

namespace djfm\ftr\tests\fixtures;

use Exception;

class NullDependencyTest
{
    public function testA()
    {
        throw new Exception('NAH');
    }

    public function testB()
    {
    }

    /**
	 * @depends testA
	 */
    public function testC()
    {

    }

    /**
     * @depends testA, testB
     */
    public function testD()
    {

    }
}
