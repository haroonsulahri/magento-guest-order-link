<?php

declare(strict_types=1);

namespace Haroone\GuestOrderLink\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Links an unassigned guest order to the matching customer account.
 *
 * @api
 */
interface GuestOrderLinkerInterface
{
    /**
     * Link the guest order to the matching customer account.
     *
     * @param int $orderId
     * @param int|null $adminUserId
     * @return OrderInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function link(int $orderId, ?int $adminUserId = null): OrderInterface;
}
