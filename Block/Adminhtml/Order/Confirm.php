<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;
use RuntimeException;

/**
 * Supplies validated order and customer details to the confirmation template.
 */
class Confirm extends Template
{
    /**
     * @param Context $context
     * @param OrderConfig $orderConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly OrderConfig $orderConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set the order displayed by the confirmation page.
     *
     * @param OrderInterface $order
     * @return self
     */
    public function setOrder(OrderInterface $order): self
    {
        return $this->setData('order', $order);
    }

    /**
     * Get the order displayed by the confirmation page.
     *
     * @return OrderInterface
     */
    public function getOrder(): OrderInterface
    {
        $order = $this->getData('order');
        if (!$order instanceof OrderInterface) {
            throw new RuntimeException('Order data is not available.');
        }

        return $order;
    }

    /**
     * Set the customer displayed by the confirmation page.
     *
     * @param CustomerInterface $customer
     * @return self
     */
    public function setCustomer(CustomerInterface $customer): self
    {
        return $this->setData('customer', $customer);
    }

    /**
     * Get the customer displayed by the confirmation page.
     *
     * @return CustomerInterface
     */
    public function getCustomer(): CustomerInterface
    {
        $customer = $this->getData('customer');
        if (!$customer instanceof CustomerInterface) {
            throw new RuntimeException('Customer data is not available.');
        }

        return $customer;
    }

    /**
     * Get the POST endpoint for the linking action.
     *
     * @return string
     */
    public function getLinkUrl(): string
    {
        return $this->getUrl(
            'haroone_linkguestordertocustomer/order/link',
            ['order_id' => (int)$this->getOrder()->getEntityId()]
        );
    }

    /**
     * Get the original Admin order-view URL.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl(
            'sales/order/view',
            ['order_id' => (int)$this->getOrder()->getEntityId()]
        );
    }

    /**
     * Build a readable customer name from the account fields.
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        return trim(
            implode(
                ' ',
                array_filter(
                    [
                        (string)$this->getCustomer()->getFirstname(),
                        (string)$this->getCustomer()->getMiddlename(),
                        (string)$this->getCustomer()->getLastname(),
                    ],
                    static fn(string $part): bool => $part !== ''
                )
            )
        );
    }

    /**
     * Check whether the current status is visible in customer order history.
     *
     * @return bool
     */
    public function isOrderStatusVisibleOnFront(): bool
    {
        return !in_array(
            (string)$this->getOrder()->getStatus(),
            $this->orderConfig->getInvisibleOnFrontStatuses(),
            true
        );
    }
}
