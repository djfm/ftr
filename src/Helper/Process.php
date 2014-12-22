<?php

namespace djfm\ftr\Helper;

class Process
{
    private static function windows()
    {
        return preg_match('/^WIN/', PHP_OS);
    }

    public static function getChildrenPIDs($pid)
    {
        $pids = [];

        if (self::windows()) {
            $command = "wmic process where (ParentProcessId=$pid) get ProcessId 2>NUL";
            $output = [];
            exec($command, $output);
            for ($i = 1; $i < count($output); $i++) {
                if (preg_match('/^\d+$/', $output[1])) {
                    $pids[] = $output[1];
                }
            }
        } else {
            $command = "pgrep -P $pid";
            $output = [];
            $ret = 1;
            exec($command, $output, $ret);
            if ($ret === 0) {
                for ($i = 0; $i < count($output); $i++) {
                    if (preg_match('/^\d+$/', $output[$i])) {
                        $pids[] = $output[$i];
                    }
                }
            }
        }

        return $pids;
    }

    public function killChildren($process)
    {
        $pid = null;
        if (is_scalar($process) && preg_match('/^\d+$/', (string) $process)) {
            $pid = (string) $process;
        } elseif (is_object($process)) {
            $getPid = [$process, 'getPid'];
            if (is_callable($getPid)) {
                $pid = $getPid();
            }
        }
        if ($pid) {
            $pids = self::getChildrenPIDs($pid);

            if (empty($pids)) {
                return false;
            }

            foreach ($pids as $childp) {
                if (self::windows()) {
                    exec("tskill $childp");
                } else {
                    exec("kill $childp");
                }
            }

            return true;
        } else {
            return false;
        }
    }
}
