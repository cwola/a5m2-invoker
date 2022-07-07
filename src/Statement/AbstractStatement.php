<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Statement;

use Stringable;
use Traversable;
use Cwola\Attribute\Readable;
use Exception;
use InvalidArgumentException;

/**
 * @property string $queryString
 */
abstract class AbstractStatement implements Stringable {
    use Readable;

    /**
     * @var int
     */
    public const PARAM_AUTODETECT = 1;
    /**
     * @var int
     */
    public const PARAM_INT = 2;
    /**
     * @var int
     */
    public const PARAM_STR = 3;
    /**
     * @var int
     */
    public const PARAM_BOOL = 4;
    /**
     * @var int
     */
    public const PARAM_NULL = 5;
    /**
     * @var int
     */
    public const PARAM_LOB = 6;

    /**
     * @var array
     */
    protected array $bindParams;

    /**
     * @var string
     */
    #[Readable]
    protected string $queryString;


    /**
     * @param string $queryString
     */
    public function __construct(string $queryString) {
        $this->queryString = $queryString;
        $this->bindParams = [];
    }

    /**
     * @param void
     *
     * @return string
     */
    public function __toString(): string {
        return $this->queryString;
    }

    /**
     * @param array|\Traversable $values
     *
     * @return $this
     */
    public function bindValues(array|Traversable $values): static {
        foreach ($values as $keyword => $value) {
            $v = $value;
            $type = static::PARAM_AUTODETECT;
            if (\is_array($value) && isset($value['value'])) {
                $v = $value['value'];
                if (isset($value['type'])) {
                    $type = $value['type'];
                }
            }
            $this->bindValue((string)$keyword, $v, $type);
        }
        return $this;
    }

    /**
     * @param string $keyword
     * @param mixed $value
     * @param int? $paramType [optional]
     *
     * @return $this
     */
    public function bindValue(string $keyword, mixed $value, ?int $paramType = null): static {
        $this->bind($keyword, $this->escape($value, $paramType === null ? static::PARAM_AUTODETECT : $paramType));
        return $this;
    }

    /**
     * @param mixed $param
     * @param int? $paramType [optional]
     *
     * @return string
     *
     * @throws \Exception
     */
    public function escape(mixed $param, ?int $paramType = null): string {
        if (\is_object($param) && $param instanceof Stringable) {
            $param = (string)$param;
        }
        if ($paramType === null) {
            $paramType = static::PARAM_AUTODETECT;
        }

        if ($paramType === static::PARAM_NULL || $paramType === static::PARAM_AUTODETECT && $param === null) {
            $param = $this->escapeNullLiteral($param);
        } else if ($paramType === static::PARAM_INT || $paramType === static::PARAM_AUTODETECT && \is_int($param)) {
            $param = $this->escapeNumberLiteral($param);
        } else if ($paramType === static::PARAM_BOOL || $paramType === static::PARAM_AUTODETECT && \is_bool($param)) {
            $param = $this->escapeBoolLiteral($param);
        } else if ($paramType === static::PARAM_STR || $paramType === static::PARAM_AUTODETECT && \is_string($param)) {
            $param = $this->escapeStringLiteral($param);
        } else if ($paramType === static::PARAM_LOB || $paramType === static::PARAM_AUTODETECT && \is_resource($param)) {
            $param = $this->escapeBytea($param);
        } else {
            throw new Exception('Invalid type.' . \gettype($param) . ' given.');
        }
        return $param;
    }

/*
  escape系関数は、期待する型と指定された値がずれていた際に、PHPErrorではなく
  InvalidArgumentExceptionを発生させるために、引数に型を指定していません。
*/

    /**
     * @param null $null [optional]
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function escapeNullLiteral($null = null): string {
        if ($null !== null) {
            throw new InvalidArgumentException('param is not null.');
        }
        return 'NULL';
    }

    /**
     * @param int $num
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function escapeNumberLiteral($num): string {
        if (!\is_int($num)) {
            throw new InvalidArgumentException('param is not int.');
        }
        return (string)$num;
    }

    /**
     * @param bool $bool
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function escapeBoolLiteral($bool): string {
        if (!\is_bool($bool)) {
            throw new InvalidArgumentException('param is not bool.');
        }
        return $bool ? 'true' : 'false';
    }

    /**
     * @param string $str
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    abstract public function escapeStringLiteral($str): string;

    /**
     * @param resource|string $str
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    abstract public function escapeBytea($str): string;

    /**
     * @param void
     *
     * @return string
     *
     * @todo This algorithm is very slow and needs to be improved.
     */
    public function createRealStatement(): string {
        $enclose = false;
        $next = '';
        $realQuery = '';

        for ($i = 0, $len = \strlen($this->queryString); $i < $len; $i++) {
            $char = $this->queryString[$i];
            $next = isset($this->queryString[$i + 1]) ? $this->queryString[$i + 1] : '';
            if ($char === "'") {
                if (!$enclose) {
                    $enclose = true;
                } else if (
                    $next === $char
                    || $this instanceof OracleStatement && $char === "\\" && $next === "'"
                ) {
                    $char .= $next;
                    $i++;
                } else {
                    $enclose = false;
                }
            } else if (!$enclose) {
                if ($char === ':') {
                    $placeHolder = $char;
                    $i++;
                    for ($i; $i < $len; $i++) {
                        $p = $this->queryString[$i];
                        if (\preg_match('/[^a-z0-9_]/i', $p) === 1) {
                            $i--;
                            break;
                        } else {
                            $placeHolder .= $p;
                        }
                    }
                    if (\strlen($placeHolder) > 1 && isset($this->bindParams[$placeHolder])) {
                        $char = $this->bindParams[$placeHolder];
                    } else {
                        $char = $placeHolder;
                    }
                }
            }
            $realQuery .= $char;
        }
        return $realQuery;
    }

    /**
     * Bind a value to $this->queryString.
     *
     * @param string $keyword
     * @param string $bindValue
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    protected function bind(string $keyword, string $bindValue): static {
        if (\strpos($keyword, ':') !== 0) {
            $keyword = ':' . $keyword;
        }
        if (\preg_match('/[^a-z0-9_]/i', substr($keyword, 1)) === 1) {
            throw new InvalidArgumentException('An invalid value was used for the placeholder name (' . $keyword . ')');
        }
        $this->bindParams[$keyword] = $bindValue;
        return $this;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function stringLiteral(string $str): string {
        return "'" . $str . "'";
    }
}
