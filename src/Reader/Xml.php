<?php

namespace Ions\Config\Reader;

use XMLReader;

/**
 * Class Xml
 * @package Ions\Config\Reader
 */
class Xml implements ReaderInterface
{
    /**
     * @var
     */
    protected $reader;

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var array
     */
    protected $textNodes = [
        XMLReader::TEXT,
        XMLReader::CDATA,
        XMLReader::WHITESPACE,
        XMLReader::SIGNIFICANT_WHITESPACE
    ];

    /**
     * @param $filename
     * @return string
     * @throws \RuntimeException
     */
    public function fromFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \RuntimeException(sprintf("File '%s' doesn't exist or not readable", $filename));
        }

        $this->reader = new XMLReader();

        $this->reader->open($filename, null, LIBXML_XINCLUDE);

        $this->directory = dirname($filename);

        set_error_handler(function ($error, $message = '') use ($filename) {
            throw new \RuntimeException(sprintf(
                'Error reading XML file "%s": %s',
                $filename,
                $message
            ), $error);
        }, E_WARNING);

        $return = $this->process();

        restore_error_handler();

        $this->reader->close();

        return $return;
    }

    /**
     * @param $string
     * @return array|string
     * @throws \RuntimeException
     */
    public function fromString($string)
    {
        if (empty($string)) {
            return [];
        }

        $this->reader = new XMLReader();

        $this->reader->xml($string, null, LIBXML_XINCLUDE);

        $this->directory = null;

        set_error_handler(function ($error, $message = '') {
            throw new \RuntimeException(sprintf(
                'Error reading XML string: %s',
                $message), $error);
        }, E_WARNING);

        $return = $this->process();

        restore_error_handler();

        $this->reader->close();

        return $return;
    }

    /**
     * @return string
     */
    protected function process()
    {
        return $this->processNextElement();
    }

    /**
     * @return string
     */
    protected function processNextElement()
    {
        $children = [];
        $text = '';

        while ($this->reader->read()) {
            if ($this->reader->nodeType === XMLReader::ELEMENT) {
                if ($this->reader->depth === 0) {
                    return $this->processNextElement();
                }

                $attributes = $this->getAttributes();

                $name = $this->reader->name;

                if ($this->reader->isEmptyElement) {
                    $child = [];
                } else {
                    $child = $this->processNextElement();
                }

                if ($attributes) {
                    if (is_string($child)) {
                        $child = ['_' => $child];
                    }

                    if (!is_array($child)) {
                        $child = [];
                    }

                    $child = array_merge($child, $attributes);
                }

                if (isset($children[$name])) {
                    if (!is_array($children[$name]) || !array_key_exists(0, $children[$name])) {
                        $children[$name] = [$children[$name]];
                    }

                    $children[$name][] = $child;
                } else {
                    $children[$name] = $child;
                }
            } elseif ($this->reader->nodeType === XMLReader::END_ELEMENT) {
                break;
            } elseif (in_array($this->reader->nodeType, $this->textNodes)) {
                $text .= $this->reader->value;
            }
        }

        return $children ?: $text;
    }

    /**
     * @return array
     */
    protected function getAttributes()
    {
        $attributes = [];

        if ($this->reader->hasAttributes) {
            while ($this->reader->moveToNextAttribute()) {
                $attributes[$this->reader->localName] = $this->reader->value;
            }

            $this->reader->moveToElement();
        }

        return $attributes;
    }
}
