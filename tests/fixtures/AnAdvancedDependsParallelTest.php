<?php

namespace djfm\ftr\tests\fixtures;

class AnAdvancedDependsParallelTest
{
	public function testA()
	{

	}

	public function data()
	{
		return [[0, 1], [1, 2], [3, 5]];
	}

	/**
	 * @dataProvider data
	 * @depends testA
	 * @parallelize
	 */
	public function testB($fib1, $fib2, $a)
	{

	}
}