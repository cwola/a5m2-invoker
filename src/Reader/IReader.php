<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Reader;

use Generator;

interface IReader {

    /**
     * @param void
     *
     * @return mixed
     */
    public function read();

    /**
     * @param void
     *
     * @return \Generator<mixed>
     */
    public function each(): Generator;

    /**
     * @param void
     *
     * @return bool
     */
    public function isEof(): bool;
}
