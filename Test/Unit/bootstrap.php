<?php

declare(strict_types=1);

$moduleRoot = dirname(__DIR__, 2);

spl_autoload_register(
    static function (string $class) use ($moduleRoot): void {
        $prefix = 'Haroone\\GuestOrderLink\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $moduleRoot . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
            . '.php';
        if (is_file($file)) {
            require $file;
        }
    },
    true,
    true
);

if (!class_exists(\Magento\User\Model\UserFactory::class)) {
    class_alias(
        \Haroone\GuestOrderLink\Test\Unit\Stub\AdminUserFactory::class,
        \Magento\User\Model\UserFactory::class
    );
}
