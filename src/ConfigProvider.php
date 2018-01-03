<?php

namespace Ions\Config;

/**
 * Class ConfigProvider
 * @package Ions\Config
 */
final class ConfigProvider extends AbstractConfig
{
    /**
     * @var
     */
    protected $config;

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->config->get($key);
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->config->{$key} = $value;
    }

    /**
     * ConfigProvider constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        if($options) {
            $this->setOptions($options);
        }
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        if(isset($options['provider']) && $options['provider']['name'] === 'php') {

            $path = $options['provider']['config_dir'];

            if(is_dir($path)) {
                $this->config = static::fromFiles($path);
            } elseif (is_file($path)) {
                $this->config = static::fromFile($path);
            }
        }
    }
}
