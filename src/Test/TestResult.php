<?php

namespace djfm\ftr\Test;

use Exception;

use djfm\ftr\Helper\ArraySerializableInterface;
use djfm\ftr\Helper\ExceptionHelper;


class TestResult implements ArraySerializableInterface
{
	private $status = 'unknown';
	private $runTime = 0;
	private $exceptions = [];

	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function setRunTime($runTime)
	{
		$this->runTime = $runTime;

		return $this;
	}

	public function getRunTime()
	{
		return $this->runTime;
	}

	public function addException(Exception $exception)
	{
		$this->exceptions[] = ExceptionHelper::toArray($exception);

		return $this;
	}

	public function getExceptions()
	{
		return $this->exceptions;
	}

    public function toArray()
    {
    	return [
    		'status' 		=> $this->status,
    		'runTime' 		=> $this->runTime,
    		'exceptions' 	=> $this->exceptions
    	];
    }

    public function fromArray(array $data)
    {
    	$this->status 		= $data['status'];
    	$this->runTime 		= $data['runTime'];
    	$this->exceptions	= $data['exceptions'];
    }
}
