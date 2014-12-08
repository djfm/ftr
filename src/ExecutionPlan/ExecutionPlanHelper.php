<?php

namespace djfm\ftr\ExecutionPlan;

class ExecutionPlanHelper
{
	public static function serializeSequence(array $sequenceOfExecutionPlans)
	{
		$plans = array_map(function($ep) {
			return [
				'class' => get_class($ep),
				'data' => $ep->toArray()
			];
		}, $sequenceOfExecutionPlans);

		return json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}

	public static function unSerializeSequence($str)
	{
		$array = json_decode($str, true);
		return array_map(function ($ep) {
			$class = $ep['class'];
			$instance = new $class();
			$instance->fromArray($ep['data']);
			return $instance;
		}, $array);
	}
}