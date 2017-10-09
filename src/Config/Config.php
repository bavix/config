<?php

namespace Bavix\Config;

use Bavix\Exceptions;
use Bavix\Exceptions\NotFound;
use Bavix\Helpers\File;
use Bavix\SDK\FileLoader;
use Bavix\SDK\Path;
use Bavix\Slice\Slice;

class Config
{

    /**
     * @var string
     */
    protected $root;

    /**
     * @var FileLoader\DataInterface[]
     */
    protected $loaders;

    /**
     * @var Slice[]
     */
    protected $slices;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @var array|Slice
     */
    protected $parameters;

    /**
     * Config constructor.
     *
     * @param string $root
     *
     * @throws Exceptions\Invalid
     * @throws Exceptions\PermissionDenied
     */
    public function __construct($root)
    {
        $this->root       = Path::slash($root);
        $this->extensions = FileLoader::extensions();

        try
        {
            $this->parameters = $this->loader('_bavix')->asSlice();
        }
        catch (NotFound\Path $path)
        {
            // file `_bavix` not found
        }
    }

    /**
     * @param string $name
     * @param string $extension
     *
     * @return string
     */
    protected function buildPath($name, $extension)
    {
        return $this->root . $name . '.' . $extension;
    }

    /**
     * @param string $name
     *
     * @return FileLoader\DataInterface
     *
     * @throws NotFound\Path
     * @throws Exceptions\Invalid
     * @throws Exceptions\PermissionDenied
     */
    protected function loader($name)
    {
        if (preg_match('~\.~', $name))
        {
            throw new Exceptions\Invalid($name);
        }

        if (isset($this->loaders[$name]))
        {
            return $this->loaders[$name];
        }

        foreach ($this->extensions as $extension)
        {
            try
            {
                $path                 = $this->buildPath($name, $extension);
                $this->loaders[$name] = FileLoader::load($path);

                return $this->loaders[$name];
            }
            catch (NotFound\Path $argumentException)
            {
                continue;
            }
        }

        throw new NotFound\Path($this->root . $name);
    }

    /**
     * @param string $name
     *
     * @return Slice
     *
     * @throws NotFound\Path
     * @throws Exceptions\Invalid
     * @throws Exceptions\PermissionDenied
     */
    public function get($name)
    {
        if (empty($this->slices[$name]))
        {
            $this->slices[$name] = $this->loader($name)->asSlice(
                $this->parameters
            );
        }

        return $this->slices[$name];
    }

    /**
     * @param string      $name
     * @param Slice|array $data
     *
     * @return bool
     *
     * @throws Exceptions\Invalid
     * @throws Exceptions\PermissionDenied
     */
    public function save($name, $data)
    {
        try
        {
            return $this->loader($name)->save($data);
        }
        catch (NotFound\Path $exception)
        {
            File::touch($this->buildPath($name, $this->extensions[0]));

            return $this->save($name, $data);
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     *
     * @throws NotFound\Path
     * @throws Exceptions\Invalid
     * @throws Exceptions\PermissionDenied
     */
    public function remove($name)
    {
        $loader = $this->loader($name);
        $path   = $loader->path();

        if (!File::isFile($path))
        {
            throw new NotFound\Path($path);
        }

        return File::remove($path);
    }

    /**
     * @return $this
     */
    public function cleanup()
    {
        $this->slices = [];
        
        return $this;
    }

}
