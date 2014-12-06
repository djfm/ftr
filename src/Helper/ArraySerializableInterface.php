<?php

namespace djfm\ftr\Helper;

interface ArraySerializableInterface
{
	public function toArray();
	public function fromArray(array $array);
}