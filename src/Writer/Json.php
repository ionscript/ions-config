<?php

namespace Ions\Config\Writer;

/**
 * Class Json
 * @package Ions\Config\Writer
 */
class Json extends AbstractWriter
{
    /**
     * @param array $config
     * @return string
     * @throws \RuntimeException
     */
    public function processConfig(array $config)
    {
        $serialized = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (false === $serialized) {
            throw new \RuntimeException(json_last_error_msg());
        }
        return $serialized;
    }
}
