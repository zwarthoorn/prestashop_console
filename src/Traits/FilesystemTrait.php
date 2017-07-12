<?php

namespace Flooris\Prestashop\Traits;

use Illuminate\Filesystem\Filesystem;
use Flooris\Prestashop\Exceptions\InvalidPathException;
use Flooris\Prestashop\Exceptions\UnreadablePathException;

trait FilesystemTrait
{

    /** @var Filesystem $filesystem */
    protected $filesystem = null;

    protected function initFilesystem()
    {
        $this->filesystem = new Filesystem;
    }

    /**
     * Ensure a given path is existing, throw an exception when it doesn't exist
     *
     * @param string $path Full path for which to validate the existence
     */
    protected function validatePathExistence($path)
    {
        if ( ! $this->filesystem->exists($path)) {
            throw new InvalidPathException("{$path} does not seem to exist.");
        }
    }

    /**
     * Ensure a given path is readable, throw an exception when it can't be read
     *
     * @param string $path Full path for which to validate the readability
     */
    protected function validatePathReadability($path)
    {
        if ( ! $this->filesystem->isReadable($path)) {
            throw new UnreadablePathException("{$path} is not readable.");
        }
    }
}
