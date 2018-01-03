<?php

namespace Ions\Config\Writer;

/**
 * Class AbstractWriter
 * @package Ions\Config\Writer
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * @param $filename
     * @param $config
     * @param bool $exclusiveLock
     * @throws \InvalidArgumentException|\RuntimeException|\Exception
     */
    public function toFile($filename, $config, $exclusiveLock = true)
    {
        if (empty($filename)) {
            throw new \InvalidArgumentException('No file name specified');
        }

        $flags = 0;

        if ($exclusiveLock) {
            $flags |= LOCK_EX;
        }

        set_error_handler(function ($error, $message = '') use ($filename) {
            throw new \RuntimeException(sprintf('Error writing to "%s": %s', $filename, $message), $error);
        }, E_WARNING);

        try {
            file_put_contents($filename, $this->toString($config), $flags);
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();
    }

    /**
     * @param $config
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function toString($config)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException(__METHOD__ . ' expects an array or Traversable config');
        }

        return $this->processConfig($config);
    }

    /**
     * @param array $config
     * @return mixed
     */
    abstract protected function processConfig(array $config);
}
