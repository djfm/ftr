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

    public static function toString(array $exception, $padding = '')
    {
        $lines = [];


        $lines[] = sprintf(
            '[exception: `%s`] %s',
            $exception['class'], $exception['message']
        );

        $lines[] = sprintf('At line %d in file `%s`', $exception['line'], $exception['file']);

        foreach (array_reverse($exception['trace']) as $n => $t) {

            $n = count($exception['trace']) - $n;
            $args = implode(', ', array_map('json_encode', $t['args']));
            
            if (strlen($args) >= 50) {
                $args = substr($args, 0, 47).'...';
            }

            $fun = $t['function'].'('.$args.')';
            if (isset($t['class'])) {
                // Not intersted in tedious internal details
                if ($t['class'] === 'PrestaShop\Ptest\Worker') {
                    ++$skipped;
                    continue;
                }
                $fun = $t['class'].$t['type'].$fun;
            }
            if ($n === 1) {
                if ($skipped > 0) {
                    $lines[] = sprintf('[skipped %d unintersting frames]', $skipped);
                }
                $bullet = '[E]';
            } else {
                $bullet = '[.]';
            }

            if (isset($t['file']) && isset($t['line'])) {
                $lines[] = sprintf(
                    '%s At %s:%s in %s',
                    $bullet,
                    $t['file'], $t['line'], $fun
                );
            } else {
                $lines[] = sprintf(
                    '%s In %s',
                    $bullet, $fun
                );
            }

        }

        return implode("\n", array_map(function ($line) use ($padding) {
            return $padding . $line;
        }, $lines));
    }
}
