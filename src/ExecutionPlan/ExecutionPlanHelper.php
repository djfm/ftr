<?php

namespace djfm\ftr\ExecutionPlan;

class ExecutionPlanHelper
{
    // serializeSequence unSerializeSequence
    public static function serialize(ExecutionPlanInterface $ep)
    {
        return json_encode([
            'class' => get_class($ep),
            'data' => $ep->toArray()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function unSerializeSequence($str)
    {
        $ep = json_decode($str, true);
        $class = $ep['class'];
        $instance = new $class();
        $instance->fromArray($ep['data']);

        return $instance;
    }
}
