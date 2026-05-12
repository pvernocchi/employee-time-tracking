<?php

namespace App\Core;

use PDO;
use PDOException;

class SetupManager
{
    private const CORE_TABLES = [
        'users',
        'time_entries',
        'leave_requests',
        'leave_balances',
        'audit_log',
        'compliance_settings',
        'data_protection_consents',
    ];

    private string $basePath;
    private string $configFile;
    private string $configExampleFile;
    private MigrationManager $migrationManager;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->configFile = $this->basePath . '/config/config.php';
        $this->configExampleFile = $this->basePath . '/config/config.example.php';
        $this->migrationManager = new MigrationManager($this->basePath . '/database/migrations');
    }

    public function getDefaultConfig(): array
    {
        return require $this->configExampleFile;
    }

    public function loadConfig(): array
    {
        if ($this->configExists()) {
            return require $this->configFile;
        }

        return $this->getDefaultConfig();
    }

    public function configExists(): bool
    {
        return file_exists($this->configFile);
    }

    public function isConfigWritable(): bool
    {
        if ($this->configExists()) {
            return is_writable($this->configFile);
        }

        return is_writable(dirname($this->configFile));
    }

    public function getStatus(?array $config = null): array
    {
        $status = [
            'configExists' => $this->configExists(),
            'configWritable' => $this->isConfigWritable(),
            'databaseConnected' => false,
            'isInstalled' => false,
            'needsUpgrade' => false,
            'legacyInstall' => false,
            'currentVersion' => null,
            'targetVersion' => $this->migrationManager->getLatestVersion(),
            'pendingMigrations' => [],
            'existingTables' => [],
            'usersCount' => 0,
            'message' => null,
        ];

        if ($config === null && !$status['configExists']) {
            return $status;
        }

        $config ??= $this->loadConfig();

        try {
            $pdo = $this->createPdo($config['database'] ?? []);
            $status['databaseConnected'] = true;

            $inspection = $this->inspectDatabase($pdo);
            return array_merge($status, $inspection);
        } catch (\RuntimeException $e) {
            $status['message'] = $e->getMessage();
            return $status;
        }
    }

    public function install(array $input): array
    {
        if (!$this->isConfigWritable()) {
            throw new \RuntimeException('The config directory is not writable. Please allow writing to config/config.php and try again.');
        }

        $config = $this->buildConfig($input);
        $pdo = $this->createPdo($config['database']);

        $this->writeConfig($config);

        $status = $this->inspectDatabase($pdo);

        if (!$status['isInstalled'] || $status['needsUpgrade']) {
            $this->migrationManager->applyPending($pdo);
            $status = $this->inspectDatabase($pdo);
        }

        if ($status['usersCount'] === 0) {
            $admin = $this->buildAdminData($input);
            $this->createAdminUser($pdo, $admin);
            $status['usersCount'] = 1;
        }

        return $this->getStatus($config);
    }

    public function upgrade(): array
    {
        if (!$this->configExists()) {
            throw new \RuntimeException('Configuration file not found. Complete the installation first.');
        }

        $config = $this->loadConfig();
        $pdo = $this->createPdo($config['database'] ?? []);

        $this->migrationManager->applyPending($pdo);

        return $this->getStatus($config);
    }

    private function inspectDatabase(PDO $pdo): array
    {
        $existingTables = [];

        foreach (self::CORE_TABLES as $table) {
            if ($this->hasTable($pdo, $table)) {
                $existingTables[] = $table;
            }
        }

        $hasAnyCoreTable = $existingTables !== [];
        $hasAllCoreTables = count($existingTables) === count(self::CORE_TABLES);
        $hasMigrationsTable = $this->hasTable($pdo, 'schema_migrations');
        $pendingMigrations = $this->migrationManager->getPendingMigrations($pdo);
        $currentVersion = null;

        if ($hasMigrationsTable) {
            $stmt = $pdo->query('SELECT version FROM schema_migrations ORDER BY version DESC LIMIT 1');
            $currentVersion = $stmt ? ($stmt->fetchColumn() ?: null) : null;
        }

        return [
            'databaseConnected' => true,
            'isInstalled' => $hasAnyCoreTable,
            'needsUpgrade' => $hasAnyCoreTable && $pendingMigrations !== [],
            'legacyInstall' => $hasAnyCoreTable && !$hasMigrationsTable,
            'currentVersion' => $currentVersion,
            'pendingMigrations' => array_map(
                static fn(array $migration): array => [
                    'version' => $migration['version'],
                    'name' => $migration['name'],
                ],
                $pendingMigrations
            ),
            'existingTables' => $existingTables,
            'usersCount' => $this->hasTable($pdo, 'users') ? $this->countUsers($pdo) : 0,
            'message' => $hasAnyCoreTable && !$hasAllCoreTables
                ? 'The database contains a partial installation. Running setup will complete any missing tables and updates.'
                : null,
        ];
    }

    private function buildConfig(array $input): array
    {
        $defaults = $this->getDefaultConfig();

        $timezone = trim((string) ($input['timezone'] ?? $defaults['app']['timezone']));
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new \RuntimeException('Please choose a valid timezone.');
        }

        $appUrl = rtrim(trim((string) ($input['app_url'] ?? '')), '/');
        if ($appUrl === '') {
            throw new \RuntimeException('Application URL is required.');
        }

        $dbHost = trim((string) ($input['db_host'] ?? ''));
        $dbName = trim((string) ($input['db_name'] ?? ''));
        $dbUser = trim((string) ($input['db_user'] ?? ''));
        $dbCharset = trim((string) ($input['db_charset'] ?? 'utf8mb4'));

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            throw new \RuntimeException('Database host, name, and user are required.');
        }

        $config = $defaults;
        $config['app']['name'] = trim((string) ($input['app_name'] ?? $defaults['app']['name'])) ?: $defaults['app']['name'];
        $config['app']['url'] = $appUrl;
        $config['app']['timezone'] = $timezone;
        $config['database']['host'] = $dbHost;
        $config['database']['name'] = $dbName;
        $config['database']['user'] = $dbUser;
        $config['database']['pass'] = (string) ($input['db_pass'] ?? '');
        $config['database']['charset'] = $dbCharset !== '' ? $dbCharset : 'utf8mb4';

        return $config;
    }

    private function buildAdminData(array $input): array
    {
        $firstName = trim((string) ($input['admin_first_name'] ?? ''));
        $lastName = trim((string) ($input['admin_last_name'] ?? ''));
        $email = trim((string) ($input['admin_email'] ?? ''));
        $password = (string) ($input['admin_password'] ?? '');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new \RuntimeException('Admin first name, last name, email, and password are required to create the first account.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Please enter a valid admin email address.');
        }

        if (strlen($password) < 8) {
            throw new \RuntimeException('Admin password must be at least 8 characters long.');
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
        ];
    }

    private function createAdminUser(PDO $pdo, array $admin): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password, first_name, last_name, role, is_active)
             VALUES (:email, :password, :first_name, :last_name, :role, :is_active)'
        );

        $stmt->execute([
            ':email' => $admin['email'],
            ':password' => password_hash($admin['password'], PASSWORD_DEFAULT),
            ':first_name' => $admin['first_name'],
            ':last_name' => $admin['last_name'],
            ':role' => 'admin',
            ':is_active' => 1,
        ]);
    }

    private function createPdo(array $databaseConfig): PDO
    {
        $host = trim((string) ($databaseConfig['host'] ?? ''));
        $name = trim((string) ($databaseConfig['name'] ?? ''));
        $user = (string) ($databaseConfig['user'] ?? '');
        $pass = (string) ($databaseConfig['pass'] ?? '');
        $charset = trim((string) ($databaseConfig['charset'] ?? 'utf8mb4')) ?: 'utf8mb4';

        if ($host === '' || $name === '' || $user === '') {
            throw new \RuntimeException('Database configuration is incomplete.');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $name, $charset);

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    private function writeConfig(array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($this->configFile, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write config/config.php. Please check file permissions.');
        }
    }

    private function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
        $stmt->execute([':table' => $table]);

        return (bool) $stmt->fetchColumn();
    }

    private function countUsers(PDO $pdo): int
    {
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        return (int) ($stmt ? $stmt->fetchColumn() : 0);
    }
}
