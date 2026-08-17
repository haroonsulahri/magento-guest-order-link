<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Test\Unit\Model;

use Haroone\LinkGuestOrderToCustomer\Model\CustomerCandidateResolver;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerCandidateResolverTest extends TestCase
{
    private CustomerRepositoryInterface&MockObject $customerRepository;
    private ScopeConfigInterface&MockObject $scopeConfig;
    private StoreManagerInterface&MockObject $storeManager;
    private CustomerCandidateResolver $resolver;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->resolver = new CustomerCandidateResolver(
            $this->customerRepository,
            $this->scopeConfig,
            $this->storeManager
        );
    }

    public function testResolvesCustomerWithinWebsiteScope(): void
    {
        $order = $this->createGuestOrder('guest@example.com', 4);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(2);
        $customer = $this->createCustomer(18, 'guest@example.com', 2);

        $this->storeManager->expects(self::once())->method('getStore')->with(4)->willReturn($store);
        $this->scopeConfig->expects(self::once())->method('getValue')->with(
            Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE,
            4
        )->willReturn(Share::SHARE_WEBSITE);
        $this->customerRepository->expects(self::once())
            ->method('get')
            ->with('guest@example.com', 2)
            ->willReturn($customer);

        self::assertSame($customer, $this->resolver->resolve($order));
    }

    public function testUsesGlobalLookupWhenAccountsAreSharedGlobally(): void
    {
        $order = $this->createGuestOrder('guest@example.com', 4);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(2);
        $customer = $this->createCustomer(18, 'guest@example.com', 7);

        $this->storeManager->method('getStore')->with(4)->willReturn($store);
        $this->scopeConfig->method('getValue')->willReturn(Share::SHARE_GLOBAL);
        $this->customerRepository->expects(self::once())
            ->method('get')
            ->with('guest@example.com', null)
            ->willReturn($customer);

        self::assertSame($customer, $this->resolver->resolve($order));
    }

    public function testRejectsOrderAlreadyAssignedToCustomer(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn('1000001');
        $order->method('getCustomerId')->willReturn(42);
        $order->method('getCustomerIsGuest')->willReturn(false);

        $this->customerRepository->expects(self::never())->method('get');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('is not an unassigned guest order');

        $this->resolver->resolve($order);
    }

    public function testRejectsGuestOrderWithoutEmail(): void
    {
        $order = $this->createGuestOrder('', 4);

        $this->customerRepository->expects(self::never())->method('get');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('does not contain a customer email address');

        $this->resolver->resolve($order);
    }

    public function testConvertsMissingCustomerToUsefulValidationError(): void
    {
        $order = $this->createGuestOrder('missing@example.com', 4);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(2);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->scopeConfig->method('getValue')->willReturn(Share::SHARE_WEBSITE);
        $this->customerRepository->method('get')->willThrowException(
            new NoSuchEntityException(__('Customer not found.'))
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'No customer account with the email address missing@example.com was found'
        );

        $this->resolver->resolve($order);
    }

    public function testRejectsCustomerFromDifferentWebsite(): void
    {
        $order = $this->createGuestOrder('guest@example.com', 4);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(2);
        $customer = $this->createCustomer(18, 'guest@example.com', 3);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->scopeConfig->method('getValue')->willReturn(Share::SHARE_WEBSITE);
        $this->customerRepository->method('get')->willReturn($customer);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('belong to different websites');

        $this->resolver->resolve($order);
    }

    private function createGuestOrder(string $email, int $storeId): OrderInterface&MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn('1000001');
        $order->method('getCustomerId')->willReturn(null);
        $order->method('getCustomerIsGuest')->willReturn(true);
        $order->method('getCustomerEmail')->willReturn($email);
        $order->method('getStoreId')->willReturn($storeId);

        return $order;
    }

    private function createCustomer(
        int $customerId,
        string $email,
        int $websiteId
    ): CustomerInterface&MockObject {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn($customerId);
        $customer->method('getEmail')->willReturn($email);
        $customer->method('getWebsiteId')->willReturn($websiteId);

        return $customer;
    }
}
