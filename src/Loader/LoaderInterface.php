<?php

namespace djfm\ftr\Loader;

interface LoaderInterface
{
    /**
     * Loads a test file.
     * @param  string $filePath The path of the file to be loaded.
     * @return mixed TestPlanInterface or false if the loader is not interested in this file.
     */
    public function loadFile($filePath);
    public function setBootstrap($filePath);
    public function setDataProviderFilter($filter);
    public function setFilter($filter);
}
