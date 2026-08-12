<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php check-integration-config.php <integration-config> <application-env>\n");
    exit(2);
}

$integrationConfigPath = realpath($argv[1]);
$applicationConfigPath = realpath($argv[2]);
if ($integrationConfigPath === false || $applicationConfigPath === false) {
    fwrite(STDERR, "One or both Magento configuration files could not be resolved.\n");
    exit(2);
}

$applicationConfig = include $applicationConfigPath;
$integrationConfig = file_get_contents($integrationConfigPath);
if ($integrationConfig === false) {
    fwrite(STDERR, "The integration-test database configuration could not be read.\n");
    exit(2);
}

$readConfigValue = static function (string $key) use ($integrationConfig): ?string {
    $pattern = "~['\"]" . preg_quote($key, '~') . "['\"]\\s*=>\\s*['\"]([^'\"]*)['\"]~";
    if (preg_match($pattern, $integrationConfig, $matches) !== 1) {
        return null;
    }

    return (string)$matches[1];
};

$testDatabase = $readConfigValue('db-name');
$testHost = $readConfigValue('db-host');
$testUser = $readConfigValue('db-user');
$testPassword = $readConfigValue('db-password');
$applicationDatabase = (string)(
    $applicationConfig['db']['connection']['default']['dbname'] ?? ''
);

if ($testDatabase === null
    || $testHost === null
    || $testUser === null
    || $testPassword === null
    || $testDatabase === ''
    || $applicationDatabase === ''
    || $testDatabase === $applicationDatabase
    || stripos($testDatabase, 'integration') === false
) {
    fwrite(STDERR, "Unsafe Magento integration-test database configuration.\n");
    exit(1);
}

$host = $testHost;
$port = null;
if (preg_match('~^(.+):(\\d+)$~', $testHost, $hostMatches) === 1) {
    $host = (string)$hostMatches[1];
    $port = (int)$hostMatches[2];
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $testDatabase);
if ($port !== null) {
    $dsn .= ';port=' . $port;
}

try {
    new PDO(
        $dsn,
        $testUser,
        $testPassword,
        [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException) {
    fwrite(STDERR, "The dedicated Magento integration-test database is not reachable.\n");
    exit(1);
}

fwrite(STDOUT, "Dedicated Magento integration-test database confirmed and reachable.\n");
