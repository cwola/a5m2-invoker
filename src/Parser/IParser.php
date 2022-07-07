<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Parser;

interface IParser {

    /**
     * @param mixed $stream
     *
     * @return mixed
     */
    public static function parse($stream);

}
