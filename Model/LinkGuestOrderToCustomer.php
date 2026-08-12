<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Model;

use Haroone\LinkGuestOrderToCustomer\Api\LinkGuestOrderToCustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CustomerAssignment;
use Magento\Sales\Model\ResourceModel\GridPool;
use Magento\User\Model\ResourceModel\User as AdminUserResource;
use Magento\User\Model\UserFactory as AdminUserFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Coordinates a validated and audited guest order assignment.
 */
class LinkGuestOrderToCustomer implements LinkGuestOrderToCustomerInterface
{
    private const LOCK_PREFIX = 'haroone_link_guest_order_to_customer_';
    private const LOCK_TIMEOUT_SECONDS = 5;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerCandidateResolver $candidateResolver
     * @param CustomerAssignment $customerAssignment
     * @param GridPool $gridPool
     * @param AdminUserFactory $adminUserFactory
     * @param AdminUserResource $adminUserResource
     * @param LockManagerInterface $lockManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerCandidateResolver $candidateResolver,
        private readonly CustomerAssignment $customerAssignment,
        private readonly GridPool $gridPool,
        private readonly AdminUserFactory $adminUserFactory,
        private readonly AdminUserResource $adminUserResource,
        private readonly LockManagerInterface $lockManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function link(int $orderId, ?int $adminUserId = null): OrderInterface
    {
        if ($orderId <= 0) {
            throw new LocalizedException(__('A valid order ID is required.'));
        }

        $lockName = self::LOCK_PREFIX . $orderId;
        if (!$this->lockManager->lock($lockName, self::LOCK_TIMEOUT_SECONDS)) {
            throw new LocalizedException(
                __('Another linking operation is already running for this order. Please try again.')
            );
        }

        try {
            $order = $this->orderRepository->get($orderId);
            if (!$order instanceof Order) {
                throw new LocalizedException(__('The order model could not be loaded safely.'));
            }

            $customer = $this->candidateResolver->resolve($order);
            $this->addAuditComment($order, $adminUserId);
            $this->customerAssignment->execute($order, $customer);
            $this->refreshSalesGrids($orderId);

            $this->logger->info(
                'Guest order linked to a customer account.',
                [
                    'order_id' => $orderId,
                    'customer_id' => (int)$customer->getId(),
                    'admin_user_id' => $adminUserId,
                ]
            );

            return $order;
        } finally {
            if (!$this->lockManager->unlock($lockName)) {
                $this->logger->warning(
                    'Guest order link lock could not be released.',
                    ['order_id' => $orderId]
                );
            }
        }
    }

    /**
     * Refresh the affected sales grids without reversing a completed assignment on grid failure.
     *
     * @param int $orderId
     * @return void
     */
    private function refreshSalesGrids(int $orderId): void
    {
        try {
            $this->gridPool->refreshByOrderId($orderId);
        } catch (Throwable) {
            $this->logger->warning(
                'Guest order was linked, but the sales grids could not be refreshed immediately.',
                ['order_id' => $orderId]
            );
        }
    }

    /**
     * Add a private order-history record for operational accountability.
     *
     * @param Order $order
     * @param int|null $adminUserId
     * @return void
     */
    private function addAuditComment(
        Order $order,
        ?int $adminUserId
    ): void {
        $history = $order->addStatusHistoryComment(
            (string)__(
                'Guest order linked to a customer account by %1.',
                $this->getAdminUserReference($adminUserId)
            )
        );
        $history->setIsVisibleOnFront(0);
        $history->setIsCustomerNotified(0);
    }

    /**
     * Resolve a human-readable Admin identity from the trusted user record.
     *
     * @param int|null $adminUserId
     * @return string
     */
    private function getAdminUserReference(?int $adminUserId): string
    {
        if ($adminUserId === null || $adminUserId <= 0) {
            return (string)__('an unknown Admin user');
        }

        $adminUser = $this->adminUserFactory->create();
        $this->adminUserResource->load($adminUser, $adminUserId);
        if (!(int)$adminUser->getId()) {
            return (string)__('an unknown Admin user');
        }

        $fullName = trim(
            trim((string)$adminUser->getFirstName())
            . ' '
            . trim((string)$adminUser->getLastName())
        );
        if ($fullName !== '') {
            return (string)__('Admin user %1', $fullName);
        }

        $username = trim((string)$adminUser->getUserName());
        if ($username !== '') {
            return (string)__('Admin user %1', $username);
        }

        return (string)__('an unknown Admin user');
    }
}
