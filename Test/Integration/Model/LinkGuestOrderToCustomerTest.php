<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Test\Integration\Model;

use Haroone\LinkGuestOrderToCustomer\Api\LinkGuestOrderToCustomerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory as OrderGridCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the real persistence boundary used when a guest order is assigned.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class LinkGuestOrderToCustomerTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testPersistsAssignmentWithoutChangingOrderBusinessData(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $orderCollection = $objectManager->get(OrderCollectionFactory::class)->create();
        /** @var Order $order */
        $order = $orderCollection->addFieldToFilter('increment_id', '100000001')->getFirstItem();

        self::assertNotNull($order->getEntityId());
        self::assertTrue((bool)$order->getCustomerIsGuest());
        self::assertNull($order->getCustomerId());

        $unchangedData = [
            'state' => (string)$order->getState(),
            'status' => (string)$order->getStatus(),
            'grand_total' => (string)$order->getGrandTotal(),
            'base_grand_total' => (string)$order->getBaseGrandTotal(),
            'payment_method' => (string)$order->getPayment()->getMethod(),
            'billing_address_id' => (int)$order->getBillingAddress()->getEntityId(),
            'shipping_address_id' => (int)$order->getShippingAddress()->getEntityId(),
        ];

        $linker = $objectManager->get(LinkGuestOrderToCustomerInterface::class);
        $linker->link((int)$order->getEntityId());

        $orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        /** @var Order $reloadedOrder */
        $reloadedOrder = $orderRepository->get((int)$order->getEntityId());

        self::assertSame(1, (int)$reloadedOrder->getCustomerId());
        self::assertFalse((bool)$reloadedOrder->getCustomerIsGuest());
        self::assertSame('customer@example.com', (string)$reloadedOrder->getCustomerEmail());
        self::assertSame($unchangedData['state'], (string)$reloadedOrder->getState());
        self::assertSame($unchangedData['status'], (string)$reloadedOrder->getStatus());
        self::assertEqualsWithDelta(
            (float)$unchangedData['grand_total'],
            (float)$reloadedOrder->getGrandTotal(),
            0.0001
        );
        self::assertEqualsWithDelta(
            (float)$unchangedData['base_grand_total'],
            (float)$reloadedOrder->getBaseGrandTotal(),
            0.0001
        );
        self::assertSame($unchangedData['payment_method'], (string)$reloadedOrder->getPayment()->getMethod());
        self::assertSame(
            $unchangedData['billing_address_id'],
            (int)$reloadedOrder->getBillingAddress()->getEntityId()
        );
        self::assertSame(
            $unchangedData['shipping_address_id'],
            (int)$reloadedOrder->getShippingAddress()->getEntityId()
        );

        $matchingHistory = array_values(array_filter(
            $reloadedOrder->getStatusHistories(),
            static function ($history): bool {
                $comment = (string)$history->getComment();
                return str_contains($comment, 'Guest order linked to a customer account')
                    && !str_contains($comment, 'customer@example.com')
                    && !str_contains($comment, 'customer ID')
                    && !str_contains($comment, 'Admin user ID');
            }
        ));
        self::assertCount(1, $matchingHistory);
        $history = $matchingHistory[0];
        self::assertFalse((bool)$history->getIsVisibleOnFront());
        self::assertFalse((bool)$history->getIsCustomerNotified());

        $gridCollection = $objectManager->get(OrderGridCollectionFactory::class)->create();
        $gridRow = $gridCollection->addFieldToFilter('entity_id', $order->getEntityId())->getFirstItem();
        self::assertSame(1, (int)$gridRow->getCustomerId());
    }
}
