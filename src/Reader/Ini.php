<?php

namespace Ions\Config\Reader;

/**
 * Class Ini
 * @package Ions\Config\Reader
 */
class Ini implements ReaderInterface
{
    /**
     * @var string
     */
    protected $nestSeparator = '.';

    /**
     * @var string
     */
    protected $directory;

    /**
     * @param $separator
     * @return $this
     */
    public function setNestSeparator($separator)
    {
        $this->nestSeparator = $separator;
        return $this;
    }

    /**
     * @return string
     */
    public function getNestSeparator()
    {
        return $this->nestSeparator;
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

        $this->directory = dirname($filename);

        set_error_handler(function ($error, $message = '') use ($filename) {
            throw new \RuntimeException(sprintf('Error reading INI file "%s": %s', $filename, $message), $error);
        }, E_WARNING);

        $ini = parse_ini_file($filename, true);

        restore_error_handler();

        return $this->process($ini);
    }

    /**
     * @param $string
     * @return array
     * @throws \RuntimeException
     */
    public function fromString($string)
    {
        if (empty($string)) {
            return [];
        }

        $this->directory = null;

        set_error_handler(function ($error, $message = '') {
            throw new \RuntimeException(sprintf('Error reading INI string: %s', $message), $error);
        }, E_WARNING);

        $ini = parse_ini_string($string, true);

        restore_error_handler();

        return $this->process($ini);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function process(array $data)
    {
        $config = [];

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                if (strpos($section, $this->nestSeparator) !== false) {

                    $sections = explode($this->nestSeparator, $section);
                    $config = array_merge_recursive($config, $this->buildNestedSection($sections, $value));

                } else {
                    $config[$section] = $this->processSection($value);
                }
            } else {
                $this->processKey($section, $value, $config);
            }
        }

        return $config;
    }

    /**
     * @param $sections
     * @param $value
     * @return array
     */
    private function buildNestedSection($sections, $value)
    {
        if (count($sections) === 0) {
            return $this->processSection($value);
        }

        $nestedSection = [];

        $first = array_shift($sections);

        $nestedSection[$first] = $this->buildNestedSection($sections, $value);

        return $nestedSection;
    }

    /**
     * @param array $section
     * @return array
     */
    protected function processSection(array $section)
    {
        $config = [];

        foreach ($section as $key => $value) {
            $this->processKey($key, $value, $config);
        }

        return $config;
    }

    /**
     * @param $key
     * @param $value
     * @param array $config
     * @throws \RuntimeException
     */
    protected function processKey($key, $value, array &$config)
    {
        if (strpos($key, $this->nestSeparator) !== false) {

            $pieces = explode($this->nestSeparator, $key, 2);

            if (!strlen($pieces[0]) || !strlen($pieces[1])) {
                throw new \RuntimeException(sprintf('Invalid key "%s"', $key));
            } elseif (!isset($config[$pieces[0]])) {
                if ($pieces[0] === '0' && !empty($config)) {
                    $config = [$pieces[0] => $config];
                } else {
                    $config[$pieces[0]] = [];
                }
            } elseif (!is_array($config[$pieces[0]])) {
                throw new \RuntimeException(sprintf('Cannot create sub-key for "%s", as key already exists', $pieces[0]));
            }

            $this->processKey($pieces[1], $value, $config[$pieces[0]]);

        } else {
            if ($key === '@include') {

                if ($this->directory === null) {
                    throw new \RuntimeException('Cannot process @include statement for a string config');
                }

                $reader = clone $this;

                $include = $reader->fromFile($this->directory . '/' . $value);

                $config = array_replace_recursive($config, $include);

            } else {
                $config[$key] = $value;
            }
        }
    }
}

