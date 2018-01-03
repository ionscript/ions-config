<?php

namespace Ions\Config;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * Class Config
 * @package Ions\Config
 */
class Config implements Countable, Iterator, ArrayAccess
{
    /**
     * @var bool
     */
    protected $allowModifications;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var
     */
    protected $skipNextIteration;

    /**
     * Config constructor.
     * @param array $array
     * @param bool $allowModifications
     */
    public function __construct(array $array, $allowModifications = true)
    {
        $this->allowModifications = (bool)$allowModifications;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->data[$key] = new static($value, $this->allowModifications);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return $default;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $value
     * @throws \RuntimeException
     */
    public function __set($name, $value)
    {
        if ($this->allowModifications) {
            if (is_array($value)) {
                $value = new static($value, true);
            }

            if (null === $name) {
                $this->data[] = $value;
            } else {
                $this->data[$name] = $value;
            }

        } else {
            throw new \RuntimeException('Config is read only');
        }
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $array = [];
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = clone $value;
            } else {
                $array[$key] = $value;
            }
        }
        $this->data = $array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        $data = $this->data;

        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @param $name
     * @throws \InvalidArgumentException
     */
    public function __unset($name)
    {
        if (!$this->allowModifications) {
            throw new \InvalidArgumentException('Config is read only');
        } elseif (isset($this->data[$name])) {
            unset($this->data[$name]);
            $this->skipNextIteration = true;
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->skipNextIteration = false;
        return current($this->data);
    }

    /**
     * @return int|null|string
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @return void
     */
    public function next()
    {
        if ($this->skipNextIteration) {
            $this->skipNextIteration = false;
            return;
        }
        next($this->data);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->skipNextIteration = false;
        reset($this->data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->key() !== null);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * @param Config $merge
     * @return $this
     */
    public function merge(Config $merge)
    {
        foreach ($merge as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                if (is_int($key)) {
                    $this->data[] = $value;
                } elseif ($value instanceof self) {
                    if ($this->data[$key] instanceof self) {
                        $this->data[$key]->merge($value);
                    }
                    $this->data[$key] = new static($value->toArray(), $this->allowModifications);
                } else {
                    $this->data[$key] = $value;
                }
            } else {
                if ($value instanceof self) {
                    $this->data[$key] = new static($value->toArray(), $this->allowModifications);
                } else {
                    $this->data[$key] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * @return void
     */
    public function setReadOnly()
    {
        $this->allowModifications = false;

        foreach ($this->data as $value) {
            if ($value instanceof self) {
                $value->setReadOnly();
            }
        }
    }

    /**
     * @return bool
     */
    public function isReadOnly()
    {
        return !$this->allowModifications;
    }
}
