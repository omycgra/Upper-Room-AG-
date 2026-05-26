<?php

class Database {
    private static $instance = null;
    private $pdo;

    private $driver;
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $charset;
    private $schema;
    private $sslMode;
    private $dsn;

    private function __construct() {
        $driver = strtolower((string)Env::get('DB_DRIVER', 'mysql'));
        if (in_array($driver, ['postgres', 'postgresql'], true)) {
            $driver = 'pgsql';
        }
        $this->driver = in_array($driver, ['mysql', 'pgsql'], true) ? $driver : 'mysql';

        $this->host = (string)Env::get('DB_HOST', 'localhost');
        $this->port = (string)Env::get('DB_PORT', $this->driver === 'pgsql' ? '5432' : '3306');
        $this->db = (string)Env::get('DB_NAME', $this->driver === 'pgsql' ? 'postgres' : 'church_management');
        $this->user = (string)Env::get('DB_USER', $this->driver === 'pgsql' ? 'postgres' : 'root');
        $this->pass = (string)Env::get('DB_PASS', '');
        $this->charset = (string)Env::get('DB_CHARSET', $this->driver === 'pgsql' ? 'utf8' : 'utf8mb4');
        $this->schema = (string)Env::get('DB_SCHEMA', $this->driver === 'pgsql' ? 'public' : $this->db);
        $this->sslMode = (string)Env::get('DB_SSLMODE', $this->driver === 'pgsql' ? 'require' : '');
        $this->dsn = (string)Env::get('DB_DSN', '');

        if ($this->dsn === '') {
            if ($this->driver === 'pgsql') {
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db}";
                if ($this->sslMode !== '') {
                    $dsn .= ";sslmode={$this->sslMode}";
                }
                $this->dsn = $dsn;
            } else {
                $this->dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
            }
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset() {
        self::$instance = null;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function driver() {
        return $this->driver;
    }

    public function isMysql() {
        return $this->driver === 'mysql';
    }

    public function isPgsql() {
        return $this->driver === 'pgsql';
    }

    public function getDatabaseName() {
        return $this->db;
    }

    public function getSchemaName() {
        return $this->schema;
    }

    public function rawExec($sql) {
        return $this->pdo->exec($sql);
    }

    public function tableExists($tableName) {
        $row = $this->fetch(
            "SELECT COUNT(*) AS c
             FROM information_schema.tables
             WHERE table_schema = ?
               AND table_name = ?",
            [$this->isPgsql() ? $this->schema : $this->db, $tableName]
        );

        return (int)($row['c'] ?? 0) > 0;
    }

    public function columnExists($tableName, $columnName) {
        $row = $this->fetch(
            "SELECT COUNT(*) AS c
             FROM information_schema.columns
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?",
            [$this->isPgsql() ? $this->schema : $this->db, $tableName, $columnName]
        );

        return (int)($row['c'] ?? 0) > 0;
    }

    public function getColumnDataType($tableName, $columnName) {
        $row = $this->fetch(
            "SELECT data_type
             FROM information_schema.columns
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?
             LIMIT 1",
            [$this->isPgsql() ? $this->schema : $this->db, $tableName, $columnName]
        );

        $type = $row['data_type'] ?? null;
        return $type !== null ? strtolower(trim((string)$type)) : null;
    }

    public function query($sql, $params = []) {
        // #region debug-point member-import-timeout-db-query
        $dbgEnabled = false;
        $dbgStart = 0.0;
        try {
            $dbgFlag = defined('ROOT_PATH') ? (ROOT_PATH . '/debug-member-import-timeout.md') : '';
            if ($dbgFlag !== '' && file_exists($dbgFlag)) {
                $dbgEnabled = true;
                $dbgStart = microtime(true);
            }
        } catch (Throwable $e) {
            $dbgEnabled = false;
        }
        // #endregion debug-point member-import-timeout-db-query

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // #region debug-point member-import-timeout-db-query-end
        if ($dbgEnabled) {
            try {
                $elapsedMs = (int)round((microtime(true) - $dbgStart) * 1000);
                if ($elapsedMs >= 250) {
                    $dir = ROOT_PATH . '/.dbg';
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    $path = $dir . '/trae-debug-log-member-import-timeout.ndjson';
                    $sqlOneLine = preg_replace('/\s+/', ' ', trim((string)$sql));
                    $evt = [
                        'ts' => date('c'),
                        'sessionId' => 'member-import-timeout',
                        'point' => 'db_query',
                        'ms' => $elapsedMs,
                        'driver' => $this->driver,
                        'route' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                        'sql' => is_string($sqlOneLine) ? mb_substr($sqlOneLine, 0, 220) : '',
                        'params_count' => is_array($params) ? count($params) : 0
                    ];
                    @file_put_contents($path, json_encode($evt, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                }
            } catch (Throwable $e) {
            }
        }
        // #endregion debug-point member-import-timeout-db-query-end

        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
}
