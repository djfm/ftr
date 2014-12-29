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
    private $startedAt;
    private $tags = [];
    private $identifier = [];

    public function __construct()
    {
        // better than nothing if caller forgets to set start time
        $this->startedAt = time();
    }

    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getStartedAt()
    {
        return $this->startedAt;
    }

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

    public function setIdentifierHierarchy(array $hierarchy)
    {
        $this->identifierHierarchy = $hierarchy;

        return $this;
    }

    public function getIdentifierHierarchy()
    {
        return $this->identifierHierarchy;
    }

    public function getExceptions()
    {
        return $this->exceptions;
    }

    public function addArtefactsDir($dir)
    {
        if (is_dir($dir)) {
            $tempFile   = tempnam(null, null);
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

    public function unpackArtefactsDir($target = '')
    {
        if ($this->zippedArtefactsDir) {
            $tempFile = tempnam(null, null);
            file_put_contents($tempFile, $this->zippedArtefactsDir);
            $archive = new ZipArchive();
            $archive->open($tempFile);
            $archive->extractTo($target);
            unlink($tempFile);
        }
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function addTags(array $tags)
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }

    public function addTag($tag, $value)
    {
        $this->tags[$tag] = $value;

        return $this;
    }

    public function toArray($includeArtefactsDir = true)
    {
        $data = [
            'status' => $this->status,
            'runTime' => $this->runTime,
            'exceptions' => $this->exceptions,
            'startedAt' => $this->startedAt,
            'tags' => $this->tags,
            'identifierHierarchy' => $this->identifierHierarchy
        ];

        if ($includeArtefactsDir) {
            $data['zippedArtefactsDir'] = base64_encode($this->zippedArtefactsDir);
        }

        return $data;
    }

    public function fromArray(array $data)
    {
        $this->status = $data['status'];
        $this->runTime = $data['runTime'];
        $this->exceptions = $data['exceptions'];
        $this->startedAt = $data['startedAt'];
        $this->tags = $data['tags'];
        $this->zippedArtefactsDir = base64_decode($data['zippedArtefactsDir']);
        $this->identifierHierarchy = $data['identifierHierarchy'];
    }
}
