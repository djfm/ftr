<?php

namespace djfm\ftr\Helper;

use Exception;

class ExceptionHelper
{
    public static function toArray(Exception $exception)
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];
    }
}
