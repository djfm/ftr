<?php

namespace djfm\ftr\Loader;

use djfm\ftr\TestClass\TestClassLoader;

class Loader implements LoaderInterface
{
    private $loaders = [];

    public function __construct()
    {
        $this->addLoader(new TestClassLoader());
    }

    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;

        return $this;
    }

    public function setBootstrap($filePath)
    {
        foreach ($this->loaders as $loader) {
            $loader->setBootstrap($filePath);
        }

        return $this;
    }

    public function setDataProviderFilter($filter)
    {
        foreach ($this->loaders as $loader) {
            $loader->setDataProviderFilter($filter);
        }

        return $this;
    }

    public function setFilter($filter)
    {
        foreach ($this->loaders as $loader) {
            $loader->setFilter($filter);
        }

        return $this;
    }

    public function getLoaders()
    {
        return $this->loaders;
    }

    public function loadFile($filePath)
    {
        foreach ($this->loaders as $loader) {
            $testPlan = $loader->loadFile($filePath);
            if ($testPlan) {
                return $testPlan;
            }
        }

        return false;
    }
}
