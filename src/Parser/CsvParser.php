<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Parser;

class CsvParser implements IParser {

    /**
     * @param string $stream
     *
     * @return array|false
     */
    public static function parse($stream): array|false {
        if (!\is_string($stream)) {
            return false;
        }
        return static::splitLine($stream);
    }

    /**
     * @param string $line
     *
     * @return array
     *
     * @todo This algorithm is very slow and needs to be improved.
     */
    protected static function splitLine(string $line): array {
        $flgQuote = false;
        $enclose = false;
        $buffer = '';
        $ret = [];
        $row = 0;

        for ($i = 0, $len = \strlen($line); $i < $len; $i++) {
            $char = $line[$i];
            if ($char === '"') {
                $enclose = true;
                if ($flgQuote && $line[$i + 1] === '"') {
                    // "When using ""double quote"" in CSV field."
                    $buffer .= $char;
                    $i++;
                } else {
                    $flgQuote = !$flgQuote;
                }
            } else {
                if (($char === "\r" || $char === "\n" || $char === ',') && !$flgQuote) {
                    if (!isset($ret[$row])) {
                        $ret[$row] = [];
                    }
                    $ret[$row][] = static::valueRetrieve($buffer, $enclose);
                    $enclose = false;
                    // End of Line.
                    if ($char !== ',') {
                        if ($char === "\r" && $line[$i + 1] === "\n") {
                            $i++;
                        }
                        $row++;
                    }
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
            }
        }
        if ($buffer !== '') {
            if (!isset($ret[$row])) {
                $ret[$row] = [];
            }
            $ret[$row][] = static::valueRetrieve($buffer, $enclose);
        }
        return $ret;
    }

    /**
     * @param string $buffer
     * @param bool $isString
     *
     * @return mixed
     */
    protected static function valueRetrieve(string $buffer, bool $isString): mixed {
        $compare = strtolower($buffer);
        if ($isString) {
            return $buffer;
        } else if (\strlen($buffer) < 1 || $compare === 'null') {
            return null;
        } else if (\is_int($buffer)) {
            return (int)$buffer;
        } else if (\is_double($buffer)) {
            return (double)$buffer;
        } else if ($compare === 'true' || $compare === 'false') {
            return $compare === 'true' ? true : false;
        }
        return $buffer;
    }
}
