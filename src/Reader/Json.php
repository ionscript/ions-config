<?php

namespace Ions\Config\Reader;

/**
 * Class Json
 * @package Ions\Config\Reader
 */
class Json implements ReaderInterface
{
    /**
     * @var
     */
    protected $directory;

    /**
     * @param $filename
     * @return array
     * @throws \RuntimeException
     */
    public function fromFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \RuntimeException(sprintf("File '%s' doesn't exist or not readable", $filename));
        }

        $this->directory = dirname($filename);
        $config = $this->decode(file_get_contents($filename));

        return $this->process($config);
    }

    /**
     * @param $string
     * @return array
     */
    public function fromString($string)
    {
        if (empty($string)) {
            return [];
        }

        $this->directory = null;
        $config = $this->decode($string);

        return $this->process($config);
    }

    /**
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    protected function process(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->process($value);
            }

            if (trim($key) === '@include') {
                if ($this->directory === null) {
                    throw new \RuntimeException('Cannot process @include statement for a JSON string');
                }

                $reader = clone $this;
                unset($data[$key]);
                $data = array_replace_recursive($data, $reader->fromFile($this->directory . '/' . $value));
            }
        }
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     * @throws \RuntimeException
     */
    private function decode($data)
    {
        $config = json_decode($data, true);

        if (null !== $config && !is_array($config)) {
            throw new \RuntimeException('Invalid JSON configuration; did not return an array or object');
        }

        if (null !== $config) {
            return $config;
        }

        if (JSON_ERROR_NONE === json_last_error()) {
            return $config;
        }

        throw new \RuntimeException(json_last_error_msg());
    }
}
