<?php

namespace Ions\Config;

/**
 * Class AbstractConfig
 * @package Ions\Config
 */
abstract class AbstractConfig
{
    /**
     * @var
     */
    public static $readers;

    /**
     * @var
     */
    public static $writers;

    /**
     * @var array
     */
    protected static $extensions = [
        'ini' => Reader\Ini::class,
        'json' => Reader\Json::class,
        'xml' => Reader\Xml::class,
        'yaml' => Reader\Yaml::class
    ];

    /**
     * @var array
     */
    protected static $writerExtensions = [
        'php' => Writer\PhpArray::class,
        'ini' => Writer\Ini::class,
        'json' => Writer\Json::class,
        'xml' => Writer\Xml::class,
        'yaml' => Writer\Yaml::class
    ];

    /**
     * @param $filename
     * @param bool $returnConfigObject
     * @param bool $useIncludePath
     * @return Config|mixed
     * @throws \RuntimeException
     */
    public static function fromFile($filename, $returnConfigObject = false, $useIncludePath = false)
    {
        $filepath = $filename;

        if (!file_exists($filename)) {

            if (!$useIncludePath) {
                throw new \RuntimeException(sprintf(
                    'Filename "%s" cannot be found relative to the working directory',
                    $filename
                ));
            }

            $fromIncludePath = stream_resolve_include_path($filename);

            if (!$fromIncludePath) {
                throw new \RuntimeException(sprintf(
                    'Filename "%s" cannot be found relative to the working directory or the include_path ("%s")',
                    $filename,
                    get_include_path()
                ));
            }

            $filepath = $fromIncludePath;
        }

        $pathinfo = pathinfo($filepath);

        if (!isset($pathinfo['extension'])) {
            throw new \RuntimeException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected',
                $filename
            ));
        }

        $extension = strtolower($pathinfo['extension']);

        if ($extension === 'php') {
            if (!is_file($filepath) || !is_readable($filepath)) {
                throw new \RuntimeException(sprintf(
                    'File \'%s\' doesn\'t exist or not readable',
                    $filename
                ));
            }

            $config = include $filepath;

        } elseif (isset(static::$extensions[$extension])) {
            $reader = static::$extensions[$extension];

            if (!$reader instanceof Reader\ReaderInterface) {
                $reader = new $reader;
                static::$extensions[$extension] = $reader;
            }

            $config = $reader->fromFile($filepath);

        } else {
            throw new \RuntimeException(sprintf(
                'Unsupported config file extension: .%s',
                $pathinfo['extension']
            ));
        }

        return $returnConfigObject ? new Config($config) : $config;
    }

    /**
     * @param array $files
     * @param bool $returnConfigObject
     * @param bool $useIncludePath
     * @return array|Config
     */
    public static function fromFiles(array $files, $returnConfigObject = false, $useIncludePath = false)
    {
        $config = [];

        foreach ($files as $file) {
            $config = array_merge($config, static::fromFile($file, false, $useIncludePath));
        }

        return $returnConfigObject ? new Config($config) : $config;
    }

    /**
     * @param $filename
     * @param $config
     * @return bool
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public static function toFile($filename, $config)
    {
        if ((is_object($config) && !($config instanceof Config)) || (!is_object($config) && !is_array($config))) {
            throw new \InvalidArgumentException(
                __METHOD__ . ' $config should be an array or instance of Config'
            );
        }

        $extension = substr(strrchr($filename, '.'), 1);
        $directory = dirname($filename);

        if (!is_dir($directory)) {
            throw new \RuntimeException(
                "Directory '{$directory}' does not exists!"
            );
        }

        if (!is_writable($directory)) {
            throw new \RuntimeException(
                "Cannot write in directory '{$directory}'"
            );
        }

        if (!isset(static::$writerExtensions[$extension])) {
            throw new \RuntimeException(
                "Unsupported config file extension: '.{$extension}' for writing."
            );
        }

        $writer = static::$writerExtensions[$extension];

        if (($writer instanceof Writer\AbstractWriter) === false) {
            $writer = new $writer;
            static::$writerExtensions[$extension] = $writer;
        }

        if (is_object($config)) {
            $config = $config->toArray();
        }

        $content = $writer->processConfig($config);
        return (bool)(file_put_contents($filename, $content) !== false);
    }

    /**
     * @param $extension
     * @param $reader
     * @throws \InvalidArgumentException
     */
    public static function registerReader($extension, $reader)
    {
        $extension = strtolower($extension);

        if (!is_string($reader) && !$reader instanceof Reader\ReaderInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Reader should be plugin name, class name or ' . 'instance of %s\Reader\ReaderInterface; received "%s"',
                __NAMESPACE__,
                (is_object($reader) ? get_class($reader) : gettype($reader))
            ));
        }

        static::$extensions[$extension] = $reader;
    }

    /**
     * @param $extension
     * @param $writer
     * @throws \InvalidArgumentException
     */
    public static function registerWriter($extension, $writer)
    {
        $extension = strtolower($extension);

        if (!is_string($writer) && !$writer instanceof Writer\AbstractWriter) {
            throw new \InvalidArgumentException(sprintf(
                'Writer should be class name or ' . 'instance of %s\Writer\AbstractWriter; received "%s"',
                __NAMESPACE__,
                (is_object($writer) ? get_class($writer) : gettype($writer))
            ));
        }

        static::$writerExtensions[$extension] = $writer;
    }
}
