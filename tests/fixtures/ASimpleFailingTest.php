<?php

namespace djfm\ftr\tests\fixtures;

use Exception;

class ASimpleFailingTest
{
    public function testA()
    {
        throw new Exception('Loupé !');
    }

    public function testB()
    {

    }
}
