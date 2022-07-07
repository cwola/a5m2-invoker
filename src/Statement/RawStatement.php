<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Statement;

use InvalidArgumentException;

class RawStatement extends AbstractStatement {

    /**
     * {@inheritDoc}
     */
    public function escapeStringLiteral($str): string {
        if (!\is_string($str)) {
            throw new InvalidArgumentException('param is not string.');
        }
        return $this->stringLiteral($str);
    }

    /**
     * {@inheritDoc}
     */
    public function escapeBytea($str): string {
        if (\is_resource($str)) {
            $str = \stream_get_contents($str);
        }
        if (!\is_string($str)) {
            throw new InvalidArgumentException('expected string or opened resource, got ' . \gettype($str));
        }
        return $this->escapeStringLiteral($str);
    }
}
