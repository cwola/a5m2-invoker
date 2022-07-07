<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Reader;

use Generator;
use Cwola\A5M2\Invoker\Parser\CsvParser;
use InvalidArgumentException;
use RuntimeException;

class CsvReader implements IReader {

    /**
     * @var array
     */
    protected array $stream;

    /**
     * @param string|resource $stream
     *
     * @return $this
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function __construct($stream) {
        if (\is_resource($stream)) {
            $stream = $this->contents($stream);
        } else if (!\is_string($stream)) {
            throw new InvalidArgumentException('$stream must be string or resource.');
        }
        $stream = $this->parse($stream);
        if ($stream === false) {
            throw new RuntimeException('CSV parse error.');
        }
        $this->stream = $stream;
    }

    /**
     * @param void
     *
     * @return array|false
     */
    public function read(): array|false {
        $ret = \current($this->stream);
        \next($this->stream);
        return $ret;
    }

    /**
     * {@inheritDoc}
     *
     * @return \Generator<array|false>
     */
    public function each(): Generator {
        while (!$this->isEof()) {
            yield $this->read();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEof(): bool {
        return \key($this->stream) === null;
    }

    /**
     * @param resource $fp
     *
     * @return string|false
     */
    protected function contents($fp): string|false {
        if (!\is_resource($fp)) {
            return false;
        }
        $contents = '';
        while (!\feof($fp)) {
            $contents .= \fread($fp, 8192);
        }
        return $contents;
    }

    /**
     * @param string $stream
     *
     * @return array|false
     */
    protected function parse(string $stream): array|false {
        return CsvParser::parse($stream);
    }
}
