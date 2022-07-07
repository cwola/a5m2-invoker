<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker\Statement;

use Cwola\A5M2\Invoker\CoreSettings;
use Cwola\A5M2\Invoker\Configs;

/**
 * @param string $provider \Cwola\A5M2\Invoker\Configs::PROVIDER_xxx or 'RAW'.
 * @param string $queryString
 *
 * @return \Cwola\A5M2\Invoker\Statement\AbstractStatement
 */
function prepare(string $provider, string $queryString): AbstractStatement {
    switch ($provider) {
        case Configs::PROVIDER_ORACLE:
            $statement = 'Oracle';
            break;
        case Configs::PROVIDER_SQL_SERVER:
            $statement = 'SqlServer';
            break;
        case Configs::PROVIDER_POSTGRES:
            $statement = 'Pgsql';
            break;
        case Configs::PROVIDER_MYSQL:
            $statement = 'Mysql';
            break;
        case Configs::PROVIDER_SQLITE:
            $statement = 'Sqlite';
            break;
        case Configs::PROVIDER_DB2:
        case Configs::PROVIDER_INTERBASE:
        case Configs::PROVIDER_ACCESS:
        default:
            if (CoreSettings::$FORCE_SUPPORT_FOR_STATEMENT || \strtolower($provider) === 'raw') {
                $statement = 'Raw';
                break;
            }
            throw new \Exception('sorry... :(, "prepared statement of \'' . $provider . '\' is not yet supported in this version."');
    }
    return (new (__NAMESPACE__ . '\\' . $statement . 'Statement')($queryString));
}
