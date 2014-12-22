<?php

namespace djfm\ftr\tests\fixtures;

use PHPUnit_Framework_TestCase;
use Exception;

class AMiscTest extends PHPUnit_Framework_TestCase
{
    /**
	 * @expectedException Exception
	 */
    public function testException()
    {
        throw new Exception("This is expected!");
    }
}
