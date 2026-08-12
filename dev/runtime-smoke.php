<?php

declare(strict_types=1);

use Haroone\GuestOrderLink\Api\GuestOrderLinkerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\AddressFactory as OrderAddressFactory;
use Magento\Sales\Model\Order\ItemFactory as OrderItemFactory;
use Magento\Sales\Model\Order\PaymentFactory as OrderPaymentFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;

$magentoRoot = realpath($argv[1] ?? dirname(__DIR__, 5));
if ($magentoRoot === false || !is_file($magentoRoot . '/app/bootstrap.php')) {
    fwrite(STDERR, "Magento root could not be resolved.\n");
    exit(2);
}

require $magentoRoot . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (Throwable $exception) {
    // Area code is already set by the surrounding runtime.
}

$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$initialTransactionLevel = $connection->getTransactionLevel();
$orderId = null;
$customerId = null;
$email = 'guest-order-link-' . bin2hex(random_bytes(8)) . '@example.com';
$incrementId = 'GOL' . date('YmdHis') . random_int(1000, 9999);

try {
    $connection->beginTransaction();

    $store = $objectManager->get(StoreManagerInterface::class)->getDefaultStoreView();
    if ($store === null || !(int)$store->getId()) {
        throw new RuntimeException('A default storefront is required for the smoke test.');
    }

    $customerFactory = $objectManager->get(CustomerInterfaceFactory::class);
    $customer = $customerFactory->create();
    $customer->setWebsiteId((int)$store->getWebsiteId())
        ->setStoreId((int)$store->getId())
        ->setEmail($email)
        ->setFirstname('Example')
        ->setLastname('Customer');
    $customer = $objectManager->get(CustomerRepositoryInterface::class)->save($customer);
    $customerId = (int)$customer->getId();

    /** @var Order $order */
    $order = $objectManager->get(OrderFactory::class)->create();
    $billingAddress = $objectManager->get(OrderAddressFactory::class)->create();
    $billingAddress->setAddressType('billing')
        ->setFirstname('Example')
        ->setLastname('Customer')
        ->setStreet(['1 Example Street'])
        ->setCity('Example City')
        ->setPostcode('00000')
        ->setTelephone('0000000000')
        ->setCountryId('US');
    $shippingAddress = clone $billingAddress;
    $shippingAddress->setId(null)->setAddressType('shipping');

    $payment = $objectManager->get(OrderPaymentFactory::class)->create();
    $payment->setMethod('checkmo');

    $item = $objectManager->get(OrderItemFactory::class)->create();
    $item->setSku('guest-order-link-smoke')
        ->setName('Generated smoke-test item')
        ->setProductType('simple')
        ->setQtyOrdered(1)
        ->setPrice(12.34)
        ->setBasePrice(12.34)
        ->setRowTotal(12.34)
        ->setBaseRowTotal(12.34);

    $order->setIncrementId($incrementId)
        ->setStoreId((int)$store->getId())
        ->setState(Order::STATE_NEW)
        ->setStatus(Order::STATE_NEW)
        ->setCustomerIsGuest(true)
        ->setCustomerEmail($email)
        ->setCustomerFirstname('Example')
        ->setCustomerLastname('Customer')
        ->setOrderCurrencyCode((string)$store->getCurrentCurrencyCode())
        ->setBaseCurrencyCode((string)$store->getBaseCurrencyCode())
        ->setSubtotal(12.34)
        ->setBaseSubtotal(12.34)
        ->setGrandTotal(12.34)
        ->setBaseGrandTotal(12.34)
        ->setTotalQtyOrdered(1);
    $order->setBillingAddress($billingAddress);
    $order->setShippingAddress($shippingAddress);
    $order->setPayment($payment);
    $order->addItem($item);
    $order = $objectManager->get(OrderRepositoryInterface::class)->save($order);
    $orderId = (int)$order->getEntityId();

    $baseline = [
        'state' => (string)$order->getState(),
        'status' => (string)$order->getStatus(),
        'grand_total' => (string)$order->getGrandTotal(),
        'base_grand_total' => (string)$order->getBaseGrandTotal(),
    ];

    $objectManager->get(GuestOrderLinkerInterface::class)->link($orderId);

    $orderTable = $resource->getTableName('sales_order');
    $gridTable = $resource->getTableName('sales_order_grid');
    $historyTable = $resource->getTableName('sales_order_status_history');
    $orderRow = $connection->fetchRow(
        $connection->select()->from($orderTable)->where('entity_id = ?', $orderId)
    );
    $gridCustomerId = (int)$connection->fetchOne(
        $connection->select()->from($gridTable, ['customer_id'])->where('entity_id = ?', $orderId)
    );
    $historyRow = $connection->fetchRow(
        $connection->select()
            ->from($historyTable, ['comment', 'is_visible_on_front', 'is_customer_notified'])
            ->where('parent_id = ?', $orderId)
            ->order('entity_id DESC')
            ->limit(1)
    );

    if (!is_array($orderRow)) {
        throw new RuntimeException('The generated order could not be read after assignment.');
    }
    $orderFailures = [];
    if ((int)$orderRow['customer_id'] !== $customerId) {
        $orderFailures[] = 'customer assignment';
    }
    if ((bool)$orderRow['customer_is_guest']) {
        $orderFailures[] = 'guest flag';
    }
    if ((string)$orderRow['state'] !== $baseline['state']) {
        $orderFailures[] = 'state preservation';
    }
    if ((string)$orderRow['status'] !== $baseline['status']) {
        $orderFailures[] = 'status preservation';
    }
    if (abs((float)$orderRow['grand_total'] - (float)$baseline['grand_total']) > 0.0001) {
        $orderFailures[] = 'grand-total preservation';
    }
    if (abs((float)$orderRow['base_grand_total'] - (float)$baseline['base_grand_total']) > 0.0001) {
        $orderFailures[] = 'base-grand-total preservation';
    }
    if ($orderFailures !== []) {
        throw new RuntimeException(
            'The persisted order failed: ' . implode(', ', $orderFailures) . '.'
        );
    }
    if ($gridCustomerId !== $customerId) {
        throw new RuntimeException('The sales order grid did not receive the customer assignment.');
    }
    if (!is_array($historyRow)
        || (bool)$historyRow['is_visible_on_front']
        || (bool)$historyRow['is_customer_notified']
        || str_contains((string)$historyRow['comment'], $email)
        || str_contains((string)$historyRow['comment'], (string)$customerId)
        || str_contains((string)$historyRow['comment'], 'Admin user ID')
    ) {
        throw new RuntimeException('The private history comment did not satisfy the privacy contract.');
    }

    echo "Runtime assignment, grid refresh and private-history checks passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    while ($connection->getTransactionLevel() > $initialTransactionLevel) {
        $connection->rollBack();
    }
}

$orderLeak = $orderId !== null && (bool)$connection->fetchOne(
    $connection->select()
        ->from($resource->getTableName('sales_order'), ['entity_id'])
        ->where('entity_id = ?', $orderId)
);
$customerLeak = $customerId !== null && (bool)$connection->fetchOne(
    $connection->select()
        ->from($resource->getTableName('customer_entity'), ['entity_id'])
        ->where('entity_id = ?', $customerId)
);
if ($orderLeak || $customerLeak) {
    fwrite(STDERR, "Smoke-test rollback failed; generated test data remains.\n");
    exit(1);
}

echo "Rollback verified: no generated order or customer remains.\n";
exit($exitCode ?? 0);
