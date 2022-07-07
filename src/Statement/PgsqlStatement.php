<?php

/*

I created it with reference to the following.
- PostgreSQL: src/interfaces/libpq/fe-exec.c
  https://doxygen.postgresql.org/fe-exec_8c.html

LICENSE(PostgreSQL License)
```
PostgreSQL Database Management System
(formerly known as Postgres, then as Postgres95)

Portions Copyright (c) 1996-2011, PostgreSQL Global Development Group

Portions Copyright (c) 1994, The Regents of the University of California

Permission to use, copy, modify, and distribute this software and its
documentation for any purpose, without fee, and without a written agreement
is hereby granted, provided that the above copyright notice and this
paragraph and the following two paragraphs appear in all copies.

IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING
LOST PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS
DOCUMENTATION, EVEN IF THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.

THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS FOR A PARTICULAR PURPOSE.  THE SOFTWARE PROVIDED HEREUNDER IS
ON AN "AS IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO
PROVIDE MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
```

*/

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Statement;

use InvalidArgumentException;

class PgsqlStatement extends AbstractStatement {

    /**
     * @var bool
     * Must be set to true if server version> = 9.0.0.
     */
    public bool $useHex;

    /**
     * @var bool
     */
    public bool $standardConformingStrings;

    /**
     * @param string $queryString
     * @param bool $useHex [optional]
     * @param bool $standardConformingStrings [optional]
     */
    public function __construct(string $queryString, bool $useHex = true, bool $standardConformingStrings = true) {
        parent::__construct($queryString);
        $this->useHex = $useHex;
        $this->standardConformingStrings = $standardConformingStrings;
    }

    /**
     * {@inheritDoc}
     */
    public function escapeStringLiteral($str): string {
        if (!\is_string($str)) {
            throw new InvalidArgumentException('param is not string.');
        }
        $escaped = \str_replace("'", "''", $str);
        if (!$this->standardConformingStrings) {
            $escaped = \str_replace(
                "\\",
                '\\\\',
                $escaped
            );
        }
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

        $hexTable = '0123456789abcdef';
        $escaped = '';
        if ($this->useHex) {
            if (!$this->standardConformingStrings) {
                $escaped .= '\\';
            }
            $escaped .= '\\x';
        }
        foreach (\str_split($str, 1) as $char) {
            $ascii = \ord($char);
            if ($this->useHex) {
                $escaped .= $hexTable[($ascii >> 4) & 0xF];
                $escaped .= $hexTable[$ascii & 0xF];
            } else if ($ascii < 0x20 || $ascii > 0x7E) {
                if (!$this->standardConformingStrings) {
                    $escaped .= '\\';
                }
                $escaped .= '\\';
                $escaped .= ($ascii >> 6) + '0';
                $escaped .= (($ascii >> 3) & 07) + '0';
                $escaped .= ($ascii & 07) + '0';
            } else if ($char === "'") {
                $escaped .= "''";
            } else if ($char === "\\") {
                if (!$this->standardConformingStrings) {
                    $escaped .= '\\\\';
                }
                $escaped .= '\\\\';
            } else {
                $escaped .= $char;
            }
        }
        return $this->stringLiteral($escaped);
    }
}
