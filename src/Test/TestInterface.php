<?php

namespace djfm\ftr\Test;

use djfm\ftr\Helper\ArraySerializableInterface;

interface TestInterface extends ArraySerializableInterface
{
    public function getExpectedInputArgumentNames();
    public function setInputArgumentValue($name, $value);
    public function run();
    public function getTestIdentifier();
    public function getTestNumber();
}
