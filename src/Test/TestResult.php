<?php

namespace djfm\ftr\Test;

use Symfony\Component\Finder\Finder;
use Exception;
use ZipArchive;

use djfm\ftr\Helper\ArraySerializableInterface;
use djfm\ftr\Helper\ExceptionHelper;

class TestResult implements ArraySerializableInterface
{
    private $status = 'unknown';
    private $runTime = 0;
    private $exceptions = [];
    private $zippedArtefactsDir = null;

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

    public function addArtefactsDir($dir)
    {
        if (is_dir($dir)) {
            $tempFile    = tempnam(null, null);
            $archive    = new ZipArchive();
            $archive->open($tempFile);
            $finder = new Finder();
            foreach ($finder->files()->in($dir) as $file) {
                 $localName = $file->getRelativePathname();
                 $archive->addFile($file->getRealpath(), $localName);
            }
            $archive->close();

            $zipData = file_get_contents($tempFile);
            unlink($tempFile);
            $this->zippedArtefactsDir = $zipData;
        }
    }

    public function toArray()
    {
        return [
            'status' => $this->status,
            'runTime' => $this->runTime,
            'exceptions' => $this->exceptions,
            'zippedArtefactsDir' => base64_encode($this->zippedArtefactsDir)
        ];
    }

    public function fromArray(array $data)
    {
        $this->status = $data['status'];
        $this->runTime = $data['runTime'];
        $this->exceptions = $data['exceptions'];
        $this->zippedArtefactsDir    = base64_decode($data['zippedArtefactsDir']);
    }
}
