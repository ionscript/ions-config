<?php

namespace Ions\Config\Writer;

/**
 * Class Yaml
 * @package Ions\Config\Writer
 */
class Yaml extends AbstractWriter
{
    /**
     * @var
     */
    protected $yamlEncoder;

    /**
     * Yaml constructor.
     * @param null $yamlEncoder
     */
    public function __construct($yamlEncoder = null)
    {
        if ($yamlEncoder !== null) {
            $this->setYamlEncoder($yamlEncoder);
        } else {
            if (function_exists('yaml_emit')) {
                $this->setYamlEncoder('yaml_emit');
            }
        }
    }

    /**
     * @return mixed
     */
    public function getYamlEncoder()
    {
        return $this->yamlEncoder;
    }

    /**
     * @param $yamlEncoder
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setYamlEncoder($yamlEncoder)
    {
        if (!is_callable($yamlEncoder)) {
            throw new \InvalidArgumentException('Invalid parameter to setYamlEncoder() - must be callable');
        }

        $this->yamlEncoder = $yamlEncoder;

        return $this;
    }

    /**
     * @param array $config
     * @return array|mixed
     * @throws \RuntimeException
     */
    public function processConfig(array $config)
    {
        if (null === $this->getYamlEncoder()) {
            throw new \RuntimeException('You didn\'t specify a Yaml callback encoder');
        }

        $config = call_user_func($this->getYamlEncoder(), $config);

        if (null === $config) {
            throw new \RuntimeException('Error generating YAML data');
        }

        return $config;
    }
}
