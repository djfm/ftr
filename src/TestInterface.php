<?php

namespace djfm\ftr;

interface TestInterface extends ArraySerializableInterface
{
	public function getExpectedInputArgumentNames();
	public function setInputArguments(array $inputData = array());
	public function run();
	public function getOutputValue();
	public function getArtifacts();
	public function getUniqueIdentifier();
	public function getHumanIdentifier();
	public function getTags();
}