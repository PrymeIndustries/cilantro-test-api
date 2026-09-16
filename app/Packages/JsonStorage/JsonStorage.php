<?php

namespace App\Packages\JsonStorage;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

class JsonStorage
{
    /**
     * @var Filesystem
     */
    protected $files;
    /**
     * @var string
     */
    protected $path;

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * The settings data.
     *
     * @var array
     */
    protected $data = array();

    /**
     * The settings updated data.
     *
     * @var array
     */
    protected $updatedData = array();

    /**
     * The settings updated data.
     *
     * @var array
     */
    protected $persistedData = array();

    /**
     * Whether the store has changed since it was last loaded.
     *
     * @var boolean
     */
    protected $unsaved = false;

    /**
     * Whether the settings data are loaded.
     *
     * @var boolean
     */
    protected $loaded = false;

    /**
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param string                           $path
     */
    public function __construct($path = null)
    {
        $this->files = new Filesystem();
        $this->setPath($path ?: storage_path() . '/settings.json');
    }

    /**
     * Set the path for the JSON file.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        // If the file does not already exist, we will attempt to create it.
        if (!$this->files->exists($path)) {
            $result = $this->files->put($path, '{}');
            if ($result === false) {
                throw new \InvalidArgumentException("Could not write to $path.");
            }
        }

        if (!$this->files->isWritable($path)) {
            throw new \InvalidArgumentException("$path is not writable.");
        }

        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     * @throws FileNotFoundException
     */
    protected function read()
    {
        $contents = $this->files->get($this->path);

        $data = json_decode($contents, true);

        if ($data === null) {
            throw new \RuntimeException("Invalid JSON in {$this->path}");
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $data)
    {
        if ($data) {
            $contents = json_encode($data);
        } else {
            $contents = '{}';
        }

        $this->files->put($this->path, $contents);
    }

    /**
     * Save any changes done to the settings data.
     *
     * @return void
     */
    public function save()
    {
        if (!$this->unsaved) {
            // either nothing has been changed, or data has not been loaded, so
            // do nothing by returning early
            return;
        }

        $this->write($this->data);
        $this->unsaved = false;
    }


    /**
     * Get a specific key from the settings data.
     *
     * @param  string|array $key
     * @param  mixed        $default Optional default value.
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($default === NULL) {
            $default = ArrayUtil::get($this->defaults, $key);
        } elseif (is_array($key) && is_array($default)) {
            $default = array_merge(ArrayUtil::get($this->defaults, $key, []), $default);
        }

        $this->load();

        return ArrayUtil::get($this->data, $key, $default);
    }

    /**
     * Determine if a key exists in the settings data.
     *
     * @param  string  $key
     *
     * @return boolean
     */
    public function has($key)
    {
        $this->load();

        return ArrayUtil::has($this->data, $key);
    }

    /**
     * Set a specific key to a value in the settings data.
     *
     * @param string|array $key   Key string or associative array of key => value
     * @param mixed        $value Optional only if the first argument is an array
     */
    public function set($key, $value = null)
    {
        $this->load();
        $this->unsaved = true;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                ArrayUtil::set($this->data, $k, $v);
                ArrayUtil::set($this->updatedData, $k, $v);
            }
        } else {
            ArrayUtil::set($this->data, $key, $value);
            ArrayUtil::set($this->updatedData, $key, $value);
        }
    }

    /**
     * Unset a key in the settings data.
     *
     * @param  string $key
     */
    public function forget($key)
    {
        $this->unsaved = true;

        if ($this->has($key)) {
            ArrayUtil::forget($this->data, $key);
            ArrayUtil::forget($this->updatedData, $key);
        }
    }

    /**
     * Get all settings data.
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function all()
    {
        $this->load();

        return $this->data;
    }

    /**
     * Make sure data is loaded.
     *
     * @param boolean $force Force a reload of data. Default false.
     * @throws FileNotFoundException
     */
    public function load($force = false)
    {
        if (!$this->loaded || $force) {
            $this->data = $this->readData();
            $this->persistedData = $this->data;
            $this->data = $this->updatedData + $this->data;
            $this->loaded = true;
        }
    }

    /**
     * Read data from a store or cache
     *
     * @return array
     * @throws FileNotFoundException
     */
    private function readData()
    {
        return $this->read();
    }
}
