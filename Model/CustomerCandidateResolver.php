<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves and validates the customer eligible for a guest order assignment.
 */
class CustomerCandidateResolver
{
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Resolve the matching customer within Magento's configured sharing scope.
     *
     * @param OrderInterface $order
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function resolve(OrderInterface $order): CustomerInterface
    {
        $incrementId = (string)$order->getIncrementId();
        if ((int)$order->getCustomerId() > 0 || !$order->getCustomerIsGuest()) {
            throw new LocalizedException(
                __('Order %1 is not an unassigned guest order.', $incrementId)
            );
        }

        $email = trim((string)$order->getCustomerEmail());
        if ($email === '') {
            throw new LocalizedException(
                __('Order %1 does not contain a customer email address.', $incrementId)
            );
        }

        $store = $this->storeManager->getStore((int)$order->getStoreId());
        $websiteId = (int)$store->getWebsiteId();
        $shareScope = (int)$this->scopeConfig->getValue(
            Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE,
            (int)$order->getStoreId()
        );
        $lookupWebsiteId = $shareScope === Share::SHARE_WEBSITE ? $websiteId : null;

        try {
            $customer = $this->customerRepository->get($email, $lookupWebsiteId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(
                __(
                    'No customer account with the email address %1 was found in the applicable customer-sharing scope.',
                    $email
                ),
                $exception
            );
        }

        if ((int)$customer->getId() <= 0
            || strcasecmp(trim((string)$customer->getEmail()), $email) !== 0
        ) {
            throw new LocalizedException(
                __('The matching customer account could not be verified for order %1.', $incrementId)
            );
        }

        if ($lookupWebsiteId !== null && (int)$customer->getWebsiteId() !== $lookupWebsiteId) {
            throw new LocalizedException(
                __('The customer account and order %1 belong to different websites.', $incrementId)
            );
        }

        return $customer;
    }
}
