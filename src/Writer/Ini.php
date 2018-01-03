<?php

namespace Ions\Config\Writer;

/**
 * Class Ini
 * @package Ions\Config\Writer
 */
class Ini extends AbstractWriter
{
    /**
     * @var string
     */
    protected $nestSeparator = '.';

    /**
     * @var bool
     */
    protected $renderWithoutSections = false;

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
     * @param $withoutSections
     * @return $this
     */
    public function setRenderWithoutSectionsFlags($withoutSections)
    {
        $this->renderWithoutSections = (bool)$withoutSections;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldRenderWithoutSections()
    {
        return $this->renderWithoutSections;
    }

    /**
     * @param array $config
     * @return string
     */
    public function processConfig(array $config)
    {
        $iniString = '';

        if ($this->shouldRenderWithoutSections()) {
            $iniString .= $this->addBranch($config);
        } else {
            $config = $this->sortRootElements($config);

            foreach ($config as $sectionName => $data) {
                if (!is_array($data)) {
                    $iniString .= $sectionName . ' = ' . $this->prepareValue($data) . "\n";
                } else {
                    $iniString .= '[' . $sectionName . ']' . "\n" . $this->addBranch($data) . "\n";
                }
            }
        }

        return $iniString;
    }

    /**
     * @param array $config
     * @param array $parents
     * @return string
     */
    protected function addBranch(array $config, $parents = [])
    {
        $iniString = '';

        foreach ($config as $key => $value) {
            $group = array_merge($parents, [$key]);
            if (is_array($value)) {
                $iniString .= $this->addBranch($value, $group);
            } else {
                $iniString .= implode($this->nestSeparator, $group) . ' = ' . $this->prepareValue($value) . "\n";
            }
        }

        return $iniString;
    }

    /**
     * @param $value
     * @return string
     * @throws \RuntimeException
     */
    protected function prepareValue($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return ($value ? 'true' : 'false');
        } elseif (false === strpos($value, '"')) {
            return '"' . $value . '"';
        } else {
            throw new \RuntimeException('Value can not contain double quotes');
        }
    }

    /**
     * @param array $config
     * @return array
     */
    protected function sortRootElements(array $config)
    {
        $sections = [];
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $sections[$key] = $value;
                unset($config[$key]);
            }
        }

        foreach ($sections as $key => $value) {
            $config[$key] = $value;
        }

        return $config;
    }
}
