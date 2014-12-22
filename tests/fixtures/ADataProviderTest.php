<?php

namespace djfm\ftr\tests\fixtures;

class ADataProviderTest
{
    public function data()
    {
        return [[1], [2], [3]];
    }

    /**
	 * @dataProvider data
	 */
    public function testA($n)
    {
    }
}
