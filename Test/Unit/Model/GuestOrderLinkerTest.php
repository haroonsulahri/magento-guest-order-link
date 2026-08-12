<?php

declare(strict_types=1);

namespace Haroone\GuestOrderLink\Test\Unit\Model;

use Haroone\GuestOrderLink\Model\CustomerCandidateResolver;
use Haroone\GuestOrderLink\Model\GuestOrderLinker;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CustomerAssignment;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\ResourceModel\GridPool;
use Magento\User\Model\ResourceModel\User as AdminUserResource;
use Magento\User\Model\User as AdminUser;
use Magento\User\Model\UserFactory as AdminUserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class GuestOrderLinkerTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;
    private CustomerCandidateResolver&MockObject $candidateResolver;
    private CustomerAssignment&MockObject $customerAssignment;
    private GridPool&MockObject $gridPool;
    private AdminUserFactory&MockObject $adminUserFactory;
    private AdminUserResource&MockObject $adminUserResource;
    private AdminUser&MockObject $adminUser;
    private LockManagerInterface&MockObject $lockManager;
    private LoggerInterface&MockObject $logger;
    private GuestOrderLinker $linker;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->candidateResolver = $this->createMock(CustomerCandidateResolver::class);
        $this->customerAssignment = $this->createMock(CustomerAssignment::class);
        $this->gridPool = $this->createMock(GridPool::class);
        $this->adminUserFactory = $this->createMock(AdminUserFactory::class);
        $this->adminUserResource = $this->createMock(AdminUserResource::class);
        $this->adminUser = $this->createMock(AdminUser::class);
        $this->lockManager = $this->createMock(LockManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->adminUserFactory->method('create')->willReturn($this->adminUser);
        $this->adminUserResource->method('load')->willReturnSelf();
        $this->linker = new GuestOrderLinker(
            $this->orderRepository,
            $this->candidateResolver,
            $this->customerAssignment,
            $this->gridPool,
            $this->adminUserFactory,
            $this->adminUserResource,
            $this->lockManager,
            $this->logger
        );
    }

    public function testLinksOrderAndAddsPrivateAuditComment(): void
    {
        $order = $this->createMock(Order::class);
        $customer = $this->createMock(CustomerInterface::class);
        $history = $this->createMock(History::class);
        $customer->method('getId')->willReturn(55);
        $customer->method('getEmail')->willReturn('guest@example.com');
        $this->adminUser->method('getId')->willReturn(7);
        $this->adminUser->method('getFirstName')->willReturn('Example');
        $this->adminUser->method('getLastName')->willReturn('Administrator');
        $this->adminUser->method('getUserName')->willReturn('example.admin');

        $this->lockManager->expects(self::once())
            ->method('lock')
            ->with('haroone_guest_order_link_12', 5)
            ->willReturn(true);
        $this->lockManager->expects(self::once())
            ->method('unlock')
            ->with('haroone_guest_order_link_12')
            ->willReturn(true);
        $this->orderRepository->expects(self::once())->method('get')->with(12)->willReturn($order);
        $this->candidateResolver->expects(self::once())->method('resolve')->with($order)->willReturn($customer);
        $order->expects(self::once())
            ->method('addStatusHistoryComment')
            ->with(self::callback(static function ($comment): bool {
                $text = (string)$comment;
                return str_contains($text, 'by Admin user Example Administrator')
                    && !str_contains($text, 'customer ID')
                    && !str_contains($text, 'guest@example.com')
                    && !str_contains($text, 'Admin user ID');
            }))
            ->willReturn($history);
        $history->expects(self::once())->method('setIsVisibleOnFront')->with(0)->willReturnSelf();
        $history->expects(self::once())->method('setIsCustomerNotified')->with(0)->willReturnSelf();
        $this->customerAssignment->expects(self::once())->method('execute')->with($order, $customer);
        $this->gridPool->expects(self::once())->method('refreshByOrderId')->with(12)->willReturnSelf();
        $this->logger->expects(self::once())->method('info');

        self::assertSame($order, $this->linker->link(12, 7));
    }

    public function testUsesAdminUsernameWhenFullNameIsEmpty(): void
    {
        $order = $this->createMock(Order::class);
        $customer = $this->createMock(CustomerInterface::class);
        $history = $this->createMock(History::class);
        $customer->method('getId')->willReturn(55);
        $customer->method('getEmail')->willReturn('guest@example.com');
        $this->adminUser->method('getId')->willReturn(7);
        $this->adminUser->method('getFirstName')->willReturn('');
        $this->adminUser->method('getLastName')->willReturn('');
        $this->adminUser->method('getUserName')->willReturn('example.admin');
        $this->lockManager->method('lock')->willReturn(true);
        $this->lockManager->method('unlock')->willReturn(true);
        $this->orderRepository->method('get')->willReturn($order);
        $this->candidateResolver->method('resolve')->willReturn($customer);
        $order->expects(self::once())
            ->method('addStatusHistoryComment')
            ->with(self::callback(static function ($comment): bool {
                return str_contains((string)$comment, 'by Admin user example.admin');
            }))
            ->willReturn($history);

        self::assertSame($order, $this->linker->link(12, 7));
    }

    public function testDoesNotExposeAdminIdWhenUserRecordCannotBeResolved(): void
    {
        $order = $this->createMock(Order::class);
        $customer = $this->createMock(CustomerInterface::class);
        $history = $this->createMock(History::class);
        $customer->method('getId')->willReturn(55);
        $customer->method('getEmail')->willReturn('guest@example.com');
        $this->adminUser->method('getId')->willReturn(null);
        $this->lockManager->method('lock')->willReturn(true);
        $this->lockManager->method('unlock')->willReturn(true);
        $this->orderRepository->method('get')->willReturn($order);
        $this->candidateResolver->method('resolve')->willReturn($customer);
        $order->expects(self::once())
            ->method('addStatusHistoryComment')
            ->with(self::callback(static function ($comment): bool {
                $text = (string)$comment;
                return str_contains($text, 'by an unknown Admin user')
                    && !str_contains($text, 'Admin user ID');
            }))
            ->willReturn($history);

        self::assertSame($order, $this->linker->link(12, 7));
    }

    public function testRejectsInvalidOrderIdBeforeLocking(): void
    {
        $this->lockManager->expects(self::never())->method('lock');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('valid order ID');

        $this->linker->link(0, 7);
    }

    public function testRejectsConcurrentLinkAttempt(): void
    {
        $this->lockManager->method('lock')->willReturn(false);
        $this->lockManager->expects(self::never())->method('unlock');
        $this->orderRepository->expects(self::never())->method('get');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Another linking operation');

        $this->linker->link(12, 7);
    }

    public function testReleasesLockWhenAssignmentFails(): void
    {
        $order = $this->createMock(Order::class);
        $customer = $this->createMock(CustomerInterface::class);
        $history = $this->createMock(History::class);
        $customer->method('getId')->willReturn(55);
        $customer->method('getEmail')->willReturn('guest@example.com');
        $this->lockManager->method('lock')->willReturn(true);
        $this->lockManager->expects(self::once())->method('unlock')->willReturn(true);
        $this->orderRepository->method('get')->willReturn($order);
        $this->candidateResolver->method('resolve')->willReturn($customer);
        $order->method('addStatusHistoryComment')->willReturn($history);
        $this->customerAssignment->method('execute')->willThrowException(new RuntimeException('Save failed.'));
        $this->gridPool->expects(self::never())->method('refreshByOrderId');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Save failed');

        $this->linker->link(12, 7);
    }

    public function testGridFailureDoesNotReverseCompletedAssignment(): void
    {
        $order = $this->createMock(Order::class);
        $customer = $this->createMock(CustomerInterface::class);
        $history = $this->createMock(History::class);
        $customer->method('getId')->willReturn(55);
        $customer->method('getEmail')->willReturn('guest@example.com');
        $this->lockManager->method('lock')->willReturn(true);
        $this->lockManager->method('unlock')->willReturn(true);
        $this->orderRepository->method('get')->willReturn($order);
        $this->candidateResolver->method('resolve')->willReturn($customer);
        $order->method('addStatusHistoryComment')->willReturn($history);
        $this->gridPool->method('refreshByOrderId')->willThrowException(new RuntimeException('Grid failed.'));
        $this->logger->expects(self::once())->method('warning')->with(
            'Guest order was linked, but the sales grids could not be refreshed immediately.',
            self::arrayHasKey('exception')
        );

        self::assertSame($order, $this->linker->link(12, 7));
    }
}
