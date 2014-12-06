<?php

namespace djfm\ftr\app;

use Symfony\Component\Console\Application;

class FTR extends Application
{
	public function __construct()
	{
		parent::__construct();
		$this->add(new Command\RunCommand());
	}
}