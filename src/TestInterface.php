<?php

namespace djfm\ftr;

interface TestInterface extends ArraySerializableInterface
{
	public function getExpectedInputArgumentNames();
	public function setInputArgumentValue($name, $value);
	public function run();
	public function getOutputValue();
	public function getArtifacts();
	public function getUniqueIdentifier();
	public function getHumanIdentifier();
	public function getTags();
}