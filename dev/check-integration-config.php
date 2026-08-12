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
if ($integrationConfig === false
    || preg_match("~['\"]db-name['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]~", $integrationConfig, $matches) !== 1
) {
    fwrite(STDERR, "The integration-test database name could not be read.\n");
    exit(2);
}
$testDatabase = (string)$matches[1];
$applicationDatabase = (string)(
    $applicationConfig['db']['connection']['default']['dbname'] ?? ''
);

if ($testDatabase === ''
    || $applicationDatabase === ''
    || $testDatabase === $applicationDatabase
    || stripos($testDatabase, 'integration') === false
) {
    fwrite(STDERR, "Unsafe Magento integration-test database configuration.\n");
    exit(1);
}

fwrite(STDOUT, "Dedicated Magento integration-test database confirmed.\n");
