<?php

namespace djfm\ftr\Test;

use djfm\ftr\Helper\ArraySerializableInterface;

interface TestInterface extends ArraySerializableInterface
{
    public function run();
    public function getTestIdentifier();
    public function getTestNumber();
}
