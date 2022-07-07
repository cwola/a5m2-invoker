<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Statement;

use InvalidArgumentException;

class OracleStatement extends AbstractStatement {

    /**
     * @param string $queryString
     */
    public function __construct(string $queryString) {
        parent::__construct($queryString);
    }

    /**
     * {@inheritDoc}
     */
    public function escapeStringLiteral($str): string {
        if (!\is_string($str)) {
            throw new InvalidArgumentException('param is not string.');
        }
        $escaped = \str_replace("'", "''", $str);
        return $this->stringLiteral($escaped);
    }

    /**
     * {@inheritDoc}
     */
    public function escapeBytea($str): string {
        if (\is_resource($str)) {
            $contents = '';
            while (!\feof($str)) {
                $contents .= \fread($str, 8192);
            }
            $str = $contents;
        }
        if (!\is_string($str)) {
            throw new InvalidArgumentException('expected string or opened resource, got ' . \gettype($str));
        }
        return $this->escapeStringLiteral($str);
    }
}
