<?php
declare(strict_types=1);
namespace Core;

class DB
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $dbPath = STORAGE_ROOT . '/db/aidipanel.sqlite';
        $dir    = dirname($dbPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $this->pdo = new \PDO('sqlite:' . $dbPath, null, null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA journal_mode=WAL;');
        $this->pdo->exec('PRAGMA foreign_keys=ON;');

        $this->migrate();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function migrate(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT    NOT NULL UNIQUE,
                password_hash TEXT    NOT NULL,
                role          TEXT    NOT NULL DEFAULT 'admin',
                active        INTEGER NOT NULL DEFAULT 1,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
                last_login    TEXT
            );

            CREATE TABLE IF NOT EXISTS sites (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                domain        TEXT    NOT NULL UNIQUE,
                type          TEXT    NOT NULL DEFAULT 'php',
                php_version   TEXT    NOT NULL DEFAULT '8.3',
                webroot       TEXT    NOT NULL,
                ssl_type      TEXT    NOT NULL DEFAULT 'self-signed',
                cache_enabled INTEGER NOT NULL DEFAULT 1,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS activity_log (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER,
                action     TEXT NOT NULL,
                detail     TEXT,
                ip         TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS settings (
                key   TEXT PRIMARY KEY,
                value TEXT
            );
        ");

        // Seed default admin if not exists
        $admin = $this->row('SELECT id FROM users WHERE username = ? LIMIT 1', ['admin']);
        if (!$admin) {
            $hash = password_hash('admin', PASSWORD_BCRYPT, ['cost' => 12]);
            $this->run(
                'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)',
                ['admin', $hash, 'admin']
            );
        }
    }

    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function rows(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function row(string $sql, array $params = []): ?array
    {
        $result = $this->run($sql, $params)->fetch();
        return $result ?: null;
    }

    public function value(string $sql, array $params = []): mixed
    {
        $row = $this->row($sql, $params);
        return $row ? reset($row) : null;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public static function log(string $action, string $detail = ''): void
    {
        $db     = self::instance();
        $userId = \Core\Session::get('user_id');
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db->run(
            'INSERT INTO activity_log (user_id, action, detail, ip) VALUES (?, ?, ?, ?)',
            [$userId, $action, $detail, $ip]
        );
    }
}
