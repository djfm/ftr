<?php

namespace djfm\ftr\tests\fixtures;

class DataProviderMadnessTest
{
	public function data()
	{
		return [[0, 1], [1, 2], [3, 5]];
	}

	public function data2()
	{
		return [[0, 1], [1, 2]];
	}

	/**
	 * @dataProvider data
	 * @parallelize
	 */
	public function testA($fib1, $fib2)
	{

	}

	/**
	 * @dataProvider data2
	 * @parallelize
	 */
	public function testB($fib1, $fib2)
	{

	}

	public function testC()
	{
		
	}
}