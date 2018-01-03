<?php

namespace Ions\Config\Writer;

/**
 * Interface WriterInterface
 * @package Ions\Config\Writer
 */
interface WriterInterface
{
    /**
     * @param $filename
     * @param $config
     * @param bool $exclusiveLock
     * @return mixed
     */
    public function toFile($filename, $config, $exclusiveLock = true);

    /**
     * @param $config
     * @return mixed
     */
    public function toString($config);
}
