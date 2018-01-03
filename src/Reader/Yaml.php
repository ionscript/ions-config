<?php

namespace Ions\Config\Reader;

/**
 * Class Yaml
 * @package Ions\Config\Reader
 */
class Yaml implements ReaderInterface
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var
     */
    protected $yamlDecoder;

    /**
     * Yaml constructor.
     * @param null $yamlDecoder
     */
    public function __construct($yamlDecoder = null)
    {
        if ($yamlDecoder !== null) {
            $this->setYamlDecoder($yamlDecoder);
        } else {
            if (function_exists('yaml_parse')) {
                $this->setYamlDecoder('yaml_parse');
            }
        }
    }

    /**
     * @param $yamlDecoder
     * @return $this
     * @throws \RuntimeException
     */
    public function setYamlDecoder($yamlDecoder)
    {
        if (!is_callable($yamlDecoder)) {
            throw new \RuntimeException('Invalid parameter to setYamlDecoder() - must be callable');
        }

        $this->yamlDecoder = $yamlDecoder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getYamlDecoder()
    {
        return $this->yamlDecoder;
    }

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

        if (null === $this->getYamlDecoder()) {
            throw new \RuntimeException('You didn\'t specify a Yaml callback decoder');
        }

        $this->directory = dirname($filename);

        $config = call_user_func($this->getYamlDecoder(), file_get_contents($filename));

        if (null === $config) {
            throw new \RuntimeException('Error parsing YAML data');
        }

        return $this->process($config);
    }

    /**
     * @param $string
     * @return array
     * @throws \RuntimeException
     */
    public function fromString($string)
    {
        if (null === $this->getYamlDecoder()) {
            throw new \RuntimeException('You didn\'t specify a Yaml callback decoder');
        }

        if (empty($string)) {
            return [];
        }

        $this->directory = null;

        $config = call_user_func($this->getYamlDecoder(), $string);

        if (null === $config) {
            throw new \RuntimeException('Error parsing YAML data');
        }

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
                    throw new \RuntimeException('Cannot process @include statement for a json string');
                }

                $reader = clone $this;

                unset($data[$key]);

                $data = array_replace_recursive($data, $reader->fromFile($this->directory . '/' . $value));
            }
        }

        return $data;
    }
}
