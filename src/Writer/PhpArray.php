<?php

namespace Ions\Config\Writer;

/**
 * Class PhpArray
 * @package Ions\Config\Writer
 */
class PhpArray extends AbstractWriter
{
    const INDENT_STRING = '    ';

    /**
     * @var bool
     */
    protected $useBracketArraySyntax = false;

    /**
     * @var bool
     */
    protected $useClassNameScalars = false;

    /**
     * @param array $config
     * @return string
     */
    public function processConfig(array $config)
    {
        $arraySyntax = [
            'open' => $this->useBracketArraySyntax ? '[' : 'array(',
            'close' => $this->useBracketArraySyntax ? ']' : ')'
        ];

        return "<?php\n" . 'return ' . $arraySyntax['open'] . "\n" . $this->processIndented($config, $arraySyntax) . $arraySyntax['close'] . ";\n";
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseBracketArraySyntax($value)
    {
        $this->useBracketArraySyntax = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseClassNameScalars($value)
    {
        $this->useClassNameScalars = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseClassNameScalars()
    {
        return $this->useClassNameScalars;
    }

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
            $dirname = str_replace('\\', '\\\\', dirname($filename));
            $string = $this->toString($config);
            $string = str_replace("'" . $dirname, "__DIR__ . '", $string);
            file_put_contents($filename, $string, $flags);
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();
    }

    /**
     * @param array $config
     * @param array $arraySyntax
     * @param int $indentLevel
     * @return string
     */
    protected function processIndented(array $config, array $arraySyntax, &$indentLevel = 1)
    {
        $arrayString = '';

        foreach ($config as $key => $value) {
            $arrayString .= str_repeat(self::INDENT_STRING, $indentLevel);

            $arrayString .= (is_int($key) ? $key : $this->processStringKey($key)) . ' => ';

            if (is_array($value)) {
                if ($value === []) {
                    $arrayString .= $arraySyntax['open'] . $arraySyntax['close'] . ",\n";
                } else {
                    $indentLevel++;
                    $arrayString .= $arraySyntax['open'] . "\n" . $this->processIndented($value, $arraySyntax, $indentLevel) . str_repeat(self::INDENT_STRING, --$indentLevel) . $arraySyntax['close'] . ",\n";
                }
            } elseif (is_object($value)) {
                $arrayString .= var_export($value, true) . ",\n";
            } elseif (is_string($value)) {
                $arrayString .= $this->processStringValue($value) . ",\n";
            } elseif (is_bool($value)) {
                $arrayString .= ($value ? 'true' : 'false') . ",\n";
            } elseif ($value === null) {
                $arrayString .= "null,\n";
            } else {
                $arrayString .= $value . ",\n";
            }
        }

        return $arrayString;
    }

    /**
     * @param $value
     * @return bool|mixed|string
     */
    protected function processStringValue($value)
    {
        if ($this->useClassNameScalars && false !== ($fqnValue = $this->fqnStringToClassNameScalar($value))) {
            return $fqnValue;
        }

        return var_export($value, true);
    }

    /**
     * @param $key
     * @return bool|string
     */
    protected function processStringKey($key)
    {
        if ($this->useClassNameScalars && false !== ($fqnKey = $this->fqnStringToClassNameScalar($key))) {
            return $fqnKey;
        }

        return "'" . addslashes($key) . "'";
    }

    /**
     * @param $string
     * @return bool|string
     */
    protected function fqnStringToClassNameScalar($string)
    {
        if (strlen($string) < 1) {
            return false;
        }

        if ($string[0] !== '\\') {
            $string = '\\' . $string;
        }

        if ($this->checkStringIsFqn($string)) {
            return $string . '::class';
        }

        return false;
    }

    /**
     * @param $string
     * @return bool
     */
    protected function checkStringIsFqn($string)
    {
        if (!preg_match('/^(?:\x5c[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $string)) {
            return false;
        }

        return class_exists($string) || interface_exists($string) || trait_exists($string);
    }
}
