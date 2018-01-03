<?php

namespace Ions\Config\Reader;

/**
 * Interface ReaderInterface
 * @package Ions\Config\Reader
 */
interface ReaderInterface
{
    /**
     * @param $filename
     * @return mixed
     */
    public function fromFile($filename);

    /**
     * @param $string
     * @return mixed
     */
    public function fromString($string);
}
