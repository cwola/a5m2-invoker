<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker;

use function Cwola\A5M2\Invoker\Statement\prepare;
use DateTime;
use Traversable;
use Generator;
use SplFixedArray;
use ArrayAccess;
use Cwola\Attribute\Readable;
use Cwola\A5M2\Invoker\Statement\AbstractStatement;
use Cwola\A5M2\Invoker\Statement\RawStatement;
use Cwola\A5M2\Invoker\Reader\IReader;
use Cwola\A5M2\Invoker\Reader\CsvReader;
use Exception;

/**
 * @property string[] $outputs
 * @property string $executedQuery
 * @property int $columnCount
 * @property float $execTime
 */
class Executor {
    use Readable;

    /**
     * @var int 
     * returns an array indexed by column number as returned in your result set, starting at column 0.
     */
    public const FETCH_NUM     = 1;
    /**
     * @var int
     * returns an array indexed by column name as returned in your result set.
     */
    public const FETCH_ASSOC   = 2;
    /**
     * @var int
     * returns an array indexed by both column name and 0-indexed column number as returned in your result set.
     */
    public const FETCH_BOTH    = 3;
    /**
     * @var int
     * returns a new instance of the requested class, mapping the columns of the result set to named properties in the class.
     */
    public const FETCH_CLASS   = 4;
    /**
     * @var int
     * returns a Generator<array> indexed by column name as returned in your result set.
     */
    public const FETCH_LAZY    = 8;
    /**
     * @var int
     * returns a CSV string as returned in your result set.
     */
    public const FETCH_STRING  = 16;
    /**
     * @var int
     * returns a file resource as returned in your result set.
     */
    public const FETCH_FILE    = 32;

    /**
     * @var int
     * default fetch mode.
     */
    public const FETCH_DEFAULT = 2;

    /**
     * @var string
     */
    protected string $outputCharset;

    /**
     * @var string
     */
    protected string $provider;

    /**
     * @var string
     */
    protected string $execFile;

    /**
     * @var string
     */
    protected string $command;

    /**
     * @var string
     */
    protected string $outputFileName;

    /**
     * @var string
     */
    protected string $destDir;

    /**
     * @var string[]
     */
    #[Readable]
    protected array $outputs;

    /**
     * @var string
     */
    #[Readable]
    protected string $executedQuery;

    /**
     * @var int
     */
    #[Readable]
    protected int $columnCount;

    /**
     * @var float
     */
    #[Readable]
    protected float $execTime;  // [ms]

    /**
     * @var int
     */
    public int $defaultFetchMode;

    /**
     * @var string
     * must be ArrayAccess && iterable
     */
    public string $listType;

    /**
     * @var bool
     */
    public bool $debug;


    /**
     * @param \Cwola\A5M2\Invoker\Configs $configs
     */
    public function __construct(Configs $configs) {
        $addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $num = (new DateTime())->format('YmdHisu') . '_' . static::getSequence() . '-' . \uniqid($addr, true);
        $ds = DIRECTORY_SEPARATOR;
        $workingDir = __DIR__ . $ds . 'working' . $ds;
        $this->outputCharset = \mb_internal_encoding();
        $this->execFile = \sprintf(
            $workingDir . 'execute' . $ds . 'RunSQL_%s.sql',
            $num
        );
        $this->outputFileName = $workingDir . 'results' . $ds . 'Query-' . $num . '.csv';
        $this->destDir = $workingDir . 'fetched' . $ds;
        $this->command = \sprintf(
            '"%s" "%s" /RunSQL /FileName=%s /Encoding=%s /FilePattern=%s',
            $configs->executor,
            $configs->toOptionString(),
            $this->execFile,
            $this->outputCharset,
            $this->outputFileName
        );
        $this->provider = $configs->provider;
        $this->outputs = [];
        $this->executedQuery = '';
        $this->columnCount = 0;
        $this->execTime = 0;
        $this->defaultFetchMode = static::FETCH_DEFAULT;
        $this->listType = 'array';
        $this->debug = false;
    }

    /**
     * @param void
     */
    public function __destruct() {
        $this->rmOutputFile();
    }

    /**
     * execute SQL statement
     *
     * execute関数はPostgresのエスケープ文字列には対応していません。  
     * エスケープ文字列を使用する場合は、$queryにAbstractStatementを直接指定してください。
     *
     * statementがサポートされていないドライバを使用している場合は、RawStatementを直接指定してください。
     *
     * @param string|\Cwola\A5M2\Invoker\Statement\AbstractStatement $query
     * @param array|\Traversable $params [optional]
     *
     * @return bool
     */
    public function execute(string|AbstractStatement $query, array|Traversable $params = []): bool {
        $outputs = [];
        $status = 0;
        $stmt = null;
        if (\is_string($query)) {
            $stmt = prepare($this->provider, $query);
        }
        $stmt->bindValues($params);
        $this->outputs = $outputs;
        $this->executedQuery = $stmt->createRealStatement();
        $this->columnCount = 0;
        $this->execTime = 0;
        $this->rmOutputFile();
        \file_put_contents($this->execFile, $this->executedQuery);
        $start = \microtime(true);
        $ret = \exec($this->command, $outputs, $status);
        $this->execTime = (\microtime(true) - $start) * 1000.0;
        @\unlink($this->execFile);
        if ($this->debug) {
            // DEBUG ONLY
            $this->outputs = \array_map(function ($output) {
                return \mb_convert_encoding($output, $this->outputCharset, 'shift-jis');
            }, $outputs);
        }
        if ($ret === false || $status !== 0) {
            $this->rmOutputFile();
            $this->outputs[] = 'Failed exec(). status:' . $status;
            return false;
        }
        return true;
    }

    /**
     * alias: Executer::execute
     *
     * @param string|\Cwola\A5M2\Invoker\Statement\AbstractStatement $query
     * @param array|\Traversable $params [optional]
     *
     * @return bool
     */
    public function exec(string|AbstractStatement $query, array|Traversable $params = []): bool {
        return $this->execute($query, $params);
    }

    /**
     * @param string $filepath
     * @param string? $charset [optional]
     *
     * @return bool
     */
    public function loadFile(string $filepath, ?string $charset = null): bool {
        $this->rmOutputFile();
        if (!\is_string($charset)) {
            $charset = $this->outputCharset;
        }
        if (!\is_file($filepath) || !\is_readable($filepath)) {
            return false;
        }
        if ($charset === $this->outputCharset) {
            if (\copy($filepath, $this->outputFileName) !== true) {
                return false;
            }
        } else {
            if(!\is_int(\file_put_contents($this->outputFileName, \mb_convert_encoding(\file_get_contents($filepath), $this->outputCharset, $charset)))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param int? $fetchMode [optional]
     * @param string? $class [optional] only FETCH_CLASS.
     *
     * @return mixed|false
     */
    public function fetch(?int $fetchMode = null, ?string $class = null): mixed {
        if (!\is_int($fetchMode)) {
            $fetchMode = $this->defaultFetchMode;
        }
        switch ($fetchMode) {
            case static::FETCH_NUM:
                return $this->fetchNum();
            case static::FETCH_ASSOC:
                return $this->fetchAssoc();
            case static::FETCH_BOTH:
                return $this->fetchBoth();
            case static::FETCH_CLASS:
                return $this->fetchClass($class);
            case static::FETCH_LAZY:
                return $this->fetchLazy();
            case static::FETCH_STRING:
                return $this->fetchString();
            case static::FETCH_FILE:
                return $this->fetchFile();
            default:
        }
        return false;
    }

    /**
     * @param void
     *
     * @return array|\Traversable|false
     */
    public function fetchNum(): array|Traversable|false {
        return $this->fetchStructure(static::FETCH_NUM);
    }

    /**
     * @param void
     *
     * @return array|\Traversable|false
     */
    public function fetchAssoc(): array|Traversable|false {
        return $this->fetchStructure(static::FETCH_ASSOC);
    }

    /**
     * @param void
     *
     * @return array|\Traversable|false
     */
    public function fetchBoth(): array|Traversable|false {
        return $this->fetchStructure(static::FETCH_BOTH);
    }

    /**
     * @param string? $class [optional]
     *
     * @return array|\Traversable|false
     */
    public function fetchClass(?string $class = null): array|Traversable|false {
        return $this->fetchStructure(static::FETCH_CLASS, $class);
    }

    /**
     * @param void
     *
     * @return \Generator<array>
     */
    public function fetchLazy(): Generator {
        try {
            $fp = null;

            $filepath = $this->fetchFile();
            if ($filepath === false || !\file_exists($filepath)) {
                throw new Exception('failed fetch.');
            }

            $fp = \fopen($filepath, 'r');
            if (!\is_resource($fp)) {
                throw new Exception('failed fetch(fopen).');
            }
            $reader = new CsvReader($fp);
            \fclose($fp);
            $fp = null;

            // ヘッダ
            $headers = static::getHeader($reader);

            // ボディ
            foreach (static::each($reader) as $lines) {
                $result = \array_combine($headers, $lines);
                if ($result !== false) {
                    yield $result;
                }
            }
        } catch (Exception $e) {
            $this->outputs[] = $e->getMessage();
        } finally {
            if (\is_resource($fp)) {
                \fclose($fp);
            }
            @\unlink($filepath);
        }
    }

    /**
     * @param void
     *
     * @return string|false
     */
    public function fetchString(): string|false {
        if (!\is_file($this->outputFileName) || !\is_readable($this->outputFileName)) {
            return false;
        }
        return \preg_replace("/^\xEF\xBB\xBF/", '', \file_get_contents($this->outputFileName));
    }

    /**
     * @param void
     *
     * @return string|false
     */
    public function fetchFile(): string|false {
        if (!\is_file($this->outputFileName) || !\is_readable($this->outputFileName)) {
            return false;
        }
        $destFileName = null;
        while ($destFileName === null || \file_exists($destFileName)) {
            $destFileName = $this->destDir . \uniqid((new DateTime)->format('YmdHisu') . '_fetched_', true);
        }
        if (!\copy($this->outputFileName, $destFileName)) {
            return false;
        }
        \register_shutdown_function(function() use ($destFileName) {
            @\unlink($destFileName);
        });
        return $destFileName;
    }

    /**
     * @param int $fetchMode
     * @param string? $class [optional]
     *
     * @return array|\Traversable|false
     */
    protected function fetchStructure(int $fetchMode, ?string $class = null): array|Traversable|false {
        try {
            $fp = null;

            if (!\is_file($this->outputFileName) || !\is_readable($this->outputFileName)) {
                throw new Exception('failed fetch($this->outputFileName is not readable file.).');
            } else if (!\in_array($fetchMode, [static::FETCH_NUM, static::FETCH_ASSOC, static::FETCH_BOTH, static::FETCH_CLASS], true)) {
                throw new Exception('failed fetch(invalid argument:$fetchMode).');
            }

            if (\is_string($class)) {
                $class = '\\' . \ltrim($class, '\\');
            }
            if (!\is_string($class) || !\class_exists($class)) {
                $class = '\\Cwola\\A5M2\\Invoker\\ResultRecord';
            }
            $fp = \fopen($this->outputFileName, 'r');
            if (!\is_resource($fp)) {
                throw new Exception('failed fetch(fopen).');
            }
            $reader = new CsvReader($fp);
            \fclose($fp);
            $fp = null;

            // ヘッダ
            $headers = static::getHeader($reader);

            // ボディ
            $results = static::listRetriever($this->listType);
            $wasFixed = ($results instanceof SplFixedArray);
            $count = 0;
            $fixedSize = $wasFixed ? $results->getSize() : 0;
            $fetchClass = (($fetchMode & static::FETCH_CLASS) === static::FETCH_CLASS);
            $fetchNum = !$fetchClass && (($fetchMode & static::FETCH_NUM) === static::FETCH_NUM);
            $fetchAssoc = !$fetchClass && (($fetchMode & static::FETCH_ASSOC) === static::FETCH_ASSOC);
            foreach (static::each($reader) as $lines) {
                $count++;
                $result = $fetchClass ? new $class : [];
                foreach (\array_combine($headers, $lines) as $key => $value) {
                    if ($fetchClass) {
                        $result->$key = $value;
                    } else {
                        if ($fetchNum) {
                            $result[] = $value;
                        }
                        if ($fetchAssoc) {
                            $result[$key] = $value;
                        }
                    }
                }
                if ($wasFixed && $count > $fixedSize) {
                    $fixedSize += 100;
                    $results->setSize($fixedSize);
                }
                $results[$count - 1] = $result;
            }
            $this->columnCount = $count;
            if ($wasFixed && $results->getSize() !== $count) {
                $results->setSize($count);
            }
            return $results;
        } catch (Exception $e) {
            $this->outputs[] = $e->getMessage();
        } finally {
            if (\is_resource($fp)) {
                \fclose($fp);
            }
        }
        return false;
    }

    /**
     * @param void
     *
     * @return bool
     */
    protected function rmOutputFile(): bool {
        if (\is_file($this->outputFileName)) {
            return @\unlink($this->outputFileName);
        }
        return true;
    }

    /**
     * @param void
     *
     * @return int
     */
    protected static function getSequence(): int {
        static $SEQ = 0;
        $SEQ++;
        return $SEQ;
    }

    /**
     * @param string $listType
     *
     * @return array|\Traversable
     */
    protected static function listRetriever(string $listType): array|Traversable {
        if (\strtolower($listType) === 'array') {
            return [];
        }
        $listType = '\\' . ltrim($listType, '\\');
        if (\class_exists($listType)) {
            $list = new $listType;
            if ($list instanceof ArrayAccess && $list instanceof Traversable) {
                return $list;
            }
        }
        return [];
    }

    /**
     * @param \Cwola\A5M2\Invoker\Reader\IReader $reader
     *
     * @return array
     *
     * @throws \Exception
     */
    protected static function getHeader(IReader $reader): array {
        $headers = $reader->read();
        if (!\is_array($headers) || !isset($headers[0]) || \is_null($headers[0])) {
            throw new Exception('failed get HEADER(fgetcsv).');
        }
        $headers[0] = \preg_replace("/^\xEF\xBB\xBF/", '', $headers[0]);
        $headers[0] = \preg_replace('/^"/', '', \preg_replace('/"$/', '', $headers[0]));
        return $headers;
    }

    /**
     * @param \Cwola\A5M2\Invoker\Reader\IReader $reader
     *
     * @return \Generator<array>
     */
    protected static function each(IReader $reader): Generator {
        foreach ($reader->each() as $lines) {
            if (!\is_array($lines) || \count($lines) < 1 || \trim(\join('', $lines)) === '') {
                continue;
            }
            yield $lines;
        }
    }
}
