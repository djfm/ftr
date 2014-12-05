<?php

namespace djfm\ftr;

interface ArraySerializableInterface
{
	public function toArray();
	public function fromArray(array $array);
}