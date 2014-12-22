<?php

namespace djfm\ftr\ExecutionPlan;

class ExecutionPlanHelper
{
    // serializeSequence unSerializeSequence
    public static function serialize(ExecutionPlanInterface $executionPlan)
    {
        return json_encode([
            'class' => get_class($executionPlan),
            'data' => $executionPlan->toArray()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function unserialize($str)
    {
        $executionPlan = json_decode($str, true);
        $class = $executionPlan['class'];
        $instance = new $class();
        $instance->fromArray($executionPlan['data']);

        return $instance;
    }
}
