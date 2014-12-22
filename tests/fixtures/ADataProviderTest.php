<?php

namespace djfm\ftr\tests\fixtures;

use Exception;

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