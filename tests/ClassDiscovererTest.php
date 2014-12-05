<?php

namespace djfm\ftr\tests;

use djfm\ftr\ClassDiscoverer;

class ClassDiscovererTest extends \PHPUnit_Framework_TestCase
{
	public function testSingleClassIsFound()
	{
		$classes = ClassDiscoverer::getDeclaredClasses(__DIR__ . '/fixtures/ASimpleTest.php');
		$this->assertEquals(1, count($classes));
		$this->assertEquals('djfm\ftr\tests\fixtures\ASimpleTest', $classes[0]);
	}

	public function testNoClassIsFound()
	{
		$classes = ClassDiscoverer::getDeclaredClasses(__DIR__ . '/fixtures/NotAClass.php');
		$this->assertEmpty($classes);
	}

	public function testNoClassIsFoundInANonPHPFIle()
	{
		$classes = ClassDiscoverer::getDeclaredClasses(__DIR__ . '/fixtures/notAPHPFile.html');
		$this->assertEmpty($classes);
	}
}