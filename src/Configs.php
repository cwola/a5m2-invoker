<?php

declare(strict_types=1);

namespace Cwola\A5M2\Invoker;

use RuntimeException;

class Configs {

    /**
     * @var string
     */
    public const CONNECTION_INTERNAL = 'Internal';

    /**
     * @var string
     */
    public const PROVIDER_ORACLE = 'Oracle';
    /**
     * @var string
     */
    public const PROVIDER_DB2 = 'Db2';
    /**
     * @var string
     */
    public const PROVIDER_SQL_SERVER = 'SQL Server';
    /**
     * @var string
     */
    public const PROVIDER_POSTGRES = 'PostgreSQL';
    /**
     * @var string
     */
    public const PROVIDER_MYSQL = 'MySQL';
    /**
     * @var string
     */
    public const PROVIDER_INTERBASE = 'Interbase';
    /**
     * @var string
     */
    public const PROVIDER_SQLITE = 'SQLite';
    /**
     * @var string
     */
    public const PROVIDER_ACCESS = 'Access';

    /**
     * @var string
     */
    public const IP_V4 = 'v4';
    /**
     * @var string
     */
    public const IP_V6 = 'v6';

    /**
     * @var string
     */
    public const AUTHENTICATION_OS = 'OS';
    /**
     * @var string
     */
    public const AUTHENTICATION_SERVER = 'Server';

    /**
     * @var string
     */
    public const PROTOCOL_VERSION_20 = 20; // version <= Postgres7.3
    /**
     * @var string
     */
    public const PROTOCOL_VERSION_30 = 30; // version >= Postgres7.4

    /**
     * @var string[]
     */
    protected static array $needDirect = [
        self::PROVIDER_ORACLE
    ];

    /**
     * @var string[]
     */
    protected static array $needServerName = [
        self::PROVIDER_DB2, self::PROVIDER_SQL_SERVER, self::PROVIDER_POSTGRES,
        self::PROVIDER_MYSQL, self::PROVIDER_INTERBASE
    ];

    /**
     * @var string[]
     */
    protected static array $needDatabase = [
        self::PROVIDER_ORACLE, self::PROVIDER_DB2, self::PROVIDER_SQL_SERVER,
        self::PROVIDER_POSTGRES, self::PROVIDER_MYSQL, self::PROVIDER_INTERBASE,
        self::PROVIDER_SQLITE, self::PROVIDER_ACCESS
    ];

    /**
     * @var string[]
     */
    protected static array $needUseUnicode = [
        self::PROVIDER_ORACLE, self::PROVIDER_MYSQL
    ];

    /**
     * @var string[]
     */
    protected static array $needInitialSchemaName = [
        self::PROVIDER_ORACLE, self::PROVIDER_DB2, self::PROVIDER_SQL_SERVER,
        self::PROVIDER_MYSQL, self::PROVIDER_POSTGRES
    ];

    /**
     * @var string[]
     */
    protected static array $needAuthentication = [
        self::PROVIDER_SQL_SERVER
    ];

    /**
     * @var string[]
     */
    protected static array $needProtocolVersion = [
        self::PROVIDER_POSTGRES
    ];


    /**
     * @var string
     */
    public string $executor = '';

    /**
     * @var string
     */
    public string $connectionType = self::CONNECTION_INTERNAL;

    /**
     * @var string
     */
    public string $provider = '';

    /**
     * @var string
     */
    public string $userName = '';

    /**
     * @var string
     */
    public string $password = '';

    /**
     * @var bool
     */
    public bool $direct = false;

    /**
     * @var string
     */
    public string $serverName = '';

    /**
     * @var int
     */
    public int $port = 0;

    /**
     * @var string
     */
    public string $ipVersion = self::IP_V4;

    /**
     * @var bool
     */
    public bool $useUnicode = false;

    /**
     * @var string
     */
    public string $initialSchemaName = '';

    /**
     * @var string
     */
    public string $database = '';

    /**
     * @var string
     */
    public string $sshHostName = '';

    /**
     * @var int
     */
    public int $sshPort = 0;

    /**
     * @var string
     */
    public string $sshUserName = '';

    /**
     * @var string
     */
    public string $sshPassword = '';

    /**
     * @var string
     */
    public string $sshDestHostName = '';

    /**
     * @var int
     */
    public int $sshDestPort = 0;

    /**
     * @var string
     */
    public string $sshKeyFile = '';

    /**
     * @var string
     */
    public string $authentication = '';

    /**
     * @var int
     */
    public int $protocolVersion = 0;


    /**
     * @param string? $configPath [optional]
     *
     * @throws \RuntimeException
     */
    public function __construct(?string $configPath = null) {
        if (\is_string($configPath) && !$this->load($configPath)) {
            throw new RuntimeException('Failed to load configuration.');
        }
    }

    /**
     * @param string $configPath
     *
     * @return bool
     */
    public function load(string $configPath): bool {
        $configs = \file_get_contents($configPath);
        if (!\is_string($configs)) {
            return false;
        }
        foreach ((array)\json_decode($configs, true) as $key => $config) {
            if (\property_exists($this, $key)) {
                $this->$key = $config;
            }
        }
        return true;
    }

    /**
     * @param void
     *
     * @return string
     */
    public function toOptionString(): string {
        $params = [
            '__ConnectionType'  => $this->connectionType,
            'ProviderName'      => $this->provider,
            'UserName'          => $this->userName,
            'Password'          => $this->password,
            'IPVersion'         => $this->ipVersion
        ];
        if (\in_array($this->provider, self::$needDirect, true)) {
            $params['Direct'] = $this->boolRetrieve($this->direct);
        }
        if (\in_array($this->provider, self::$needServerName, true) || $this->provider === self::PROVIDER_ORACLE && $this->direct) {
            $params['ServerName'] = $this->serverName;
            $params['Port'] = $this->port;
        }
        if (\in_array($this->provider, self::$needDatabase, true)) {
            $params['Database'] = $this->database;
        }
        if (\in_array($this->provider, self::$needUseUnicode, true)) {
            $params['UseUnicode'] = $this->boolRetrieve($this->useUnicode);
        }
        if (\in_array($this->provider, self::$needInitialSchemaName, true)) {
            $params['InitialSchemaName'] = $this->initialSchemaName;
        }
        if ((\strlen($this->sshHostName) * $this->sshPort * \strlen($this->sshUserName) * \strlen($this->sshPassword)) > 0) {
            $params['__SSHHostName'] = $this->sshHostName;
            $params['__SSHPort'] = $this->sshPort;
            $params['__SSHUserName'] = $this->sshUserName;
            $params['__SSHPassword'] = $this->sshPassword;
            if ((\strlen($this->sshDestHostName) * $this->sshDestPort) > 0) {
                $params['__SSHDestHostName'] = $this->sshDestHostName;
                $params['__SSHDestPort'] = $this->sshDestPort;
            }
            if (\strlen($this->sshKeyFile) > 0) {
                $params['__SSHKeyFile'] = $this->sshKeyFile;
            }
        }
        if (\in_array($this->provider, self::$needAuthentication, true)) {
            $params['Authentication'] = $this->authentication;
        }
        if (\in_array($this->provider, self::$needProtocolVersion, true)) {
            $params['ProtocolVersion'] = $this->protocolVersion;
        }

        return '/Connect=' . \join(';', \array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, \array_keys($params), \array_values($params)));
    }

    /**
     * @param bool $value
     *
     * @return string
     */
    protected function boolRetrieve(bool $value): string {
        return $value ? 'True' : 'False';
    }
}
