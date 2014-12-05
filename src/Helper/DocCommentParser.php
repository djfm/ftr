<?php

namespace djfm\ftr\Helper;

use Exception;

class DocCommentParser
{
	public function __construct($comment_string)
	{
		$this->comment_string = $comment_string;
	}

	public function hasOption($name)
	{
		$exp = '/^\s*\*\s*@'.preg_quote($name).'\b/mi';
		return preg_match($exp, $this->comment_string);
	}

	public function getOption($name, $default = null)
	{
		return $this->_getOption($name, ['default' => $default, 'array' => false]);
	}

	public function getArrayOption($name, $default = null)
	{
		return $this->_getOption($name, ['default' => $default, 'array' => true]);
	}

	private function _getOption($name, array $options)
	{
		$m = [];
		$exp = '/^\s*\*\s*@'.preg_quote($name).'\b(.*?)$/mi';

		$n = preg_match_all($exp, $this->comment_string, $m);

		if ($n > 0)
		{
			if ($n > 1 && !$options['array']) {
				throw new Exception('Too many `' . $name . '` annotations, not expecting an array.');
			}

			$values = array_map(function ($match) {
				$v = trim($match);
				if ($v !== '') {
					return $v;
				} else {
					return $options['default'];
				}
			}, $m[1]);

			if ($options['array']) {
				return $values;
			} else {
				return $values[0];
			}
		}
		else
		{
			if ($options['array']) {
				return [$options['default']];
			} else {
				return $options['default'];
			}
		}
	}
}