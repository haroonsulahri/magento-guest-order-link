<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Test\Unit\Plugin\Adminhtml\Order;

use Haroone\LinkGuestOrderToCustomer\Model\CustomerCandidateResolver;
use Haroone\LinkGuestOrderToCustomer\Plugin\Adminhtml\Order\LinkGuestOrderToCustomerButtonPlugin;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkGuestOrderToCustomerButtonPluginTest extends TestCase
{
    private AuthorizationInterface&MockObject $authorization;
    private Escaper&MockObject $escaper;
    private CustomerCandidateResolver&MockObject $customerCandidateResolver;
    private LinkGuestOrderToCustomerButtonPlugin $plugin;

    protected function setUp(): void
    {
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->customerCandidateResolver = $this->createMock(CustomerCandidateResolver::class);
        $this->plugin = new LinkGuestOrderToCustomerButtonPlugin(
            $this->authorization,
            $this->escaper,
            $this->customerCandidateResolver
        );
    }

    public function testAddsButtonForAuthorizedUnassignedGuestOrder(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $view = $this->createMock(View::class);
        $order = $this->createMock(Order::class);
        $this->authorization->method('isAllowed')->with('Haroone_LinkGuestOrderToCustomer::link')->willReturn(true);
        $order->method('getEntityId')->willReturn(12);
        $order->method('getCustomerId')->willReturn(null);
        $order->method('getCustomerIsGuest')->willReturn(true);
        $this->customerCandidateResolver->expects(self::once())
            ->method('resolve')
            ->with($order)
            ->willReturn($this->createMock(CustomerInterface::class));
        $view->method('getOrder')->willReturn($order);
        $view->method('getUrl')->with('haroone_linkguestordertocustomer/order/confirm')->willReturn('https://admin/link');
        $this->escaper->method('escapeJs')->with('https://admin/link')->willReturn('https://admin/link');
        $view->expects(self::once())
            ->method('addButton')
            ->with(
                'haroone_link_guest_order_to_customer',
                self::callback(static function (array $data): bool {
                    return $data['id'] === 'haroone-link-guest-order-to-customer'
                        && (string)$data['label'] === 'Link Guest Order to Customer'
                        && $data['onclick'] === "setLocation('https://admin/link')";
                }),
                1,
                10
            )
            ->willReturnSelf();

        self::assertSame([$layout], $this->plugin->beforeSetLayout($view, $layout));
    }

    public function testDoesNotAddButtonWithoutPermission(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $view = $this->createMock(View::class);
        $this->authorization->method('isAllowed')->willReturn(false);
        $this->customerCandidateResolver->expects(self::never())->method('resolve');
        $view->expects(self::never())->method('addButton');

        self::assertSame([$layout], $this->plugin->beforeSetLayout($view, $layout));
    }

    public function testDoesNotAddButtonForAssignedOrder(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $view = $this->createMock(View::class);
        $order = $this->createMock(Order::class);
        $this->authorization->method('isAllowed')->willReturn(true);
        $order->method('getEntityId')->willReturn(12);
        $order->method('getCustomerId')->willReturn(55);
        $order->method('getCustomerIsGuest')->willReturn(false);
        $view->method('getOrder')->willReturn($order);
        $this->customerCandidateResolver->expects(self::never())->method('resolve');
        $view->expects(self::never())->method('addButton');

        self::assertSame([$layout], $this->plugin->beforeSetLayout($view, $layout));
    }

    public function testDoesNotAddButtonWhenNoMatchingCustomerExists(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $view = $this->createMock(View::class);
        $order = $this->createMock(Order::class);
        $this->authorization->method('isAllowed')->willReturn(true);
        $order->method('getEntityId')->willReturn(12);
        $order->method('getCustomerId')->willReturn(null);
        $order->method('getCustomerIsGuest')->willReturn(true);
        $view->method('getOrder')->willReturn($order);
        $this->customerCandidateResolver->expects(self::once())
            ->method('resolve')
            ->with($order)
            ->willThrowException(new LocalizedException(__('No matching customer.')));
        $view->expects(self::never())->method('addButton');

        self::assertSame([$layout], $this->plugin->beforeSetLayout($view, $layout));
    }
}
