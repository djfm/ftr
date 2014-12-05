<?php

namespace djfm\ftr\Helper;

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
		$m = [];
		$exp = '/^\s*\*\s*@'.preg_quote($name).'\b(.*?)$/mi';
		if (preg_match($exp, $this->comment_string, $m))
		{
			$value = trim($m[1]);
			return $value !== '' ? $value : $default;
		}
		else
		{
			return $default;
		}
	}
}