<?php

declare(strict_types=1);

namespace Haroone\GuestOrderLink\Test\Unit\Stub;

use LogicException;
use Magento\User\Model\User;

/**
 * Isolated unit-test substitute for Magento's generated UserFactory class.
 */
class AdminUserFactory
{
    /**
     * The PHPUnit mock replaces this method during unit tests.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data = []): User
    {
        throw new LogicException('The isolated Admin user factory must be mocked.');
    }
}
