<?php

declare(strict_types=1);

namespace Haroone\GuestOrderLink\Controller\Adminhtml\Order;

use Haroone\GuestOrderLink\Api\GuestOrderLinkerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Executes the validated guest order assignment from Admin.
 */
class Link extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Haroone_GuestOrderLink::link';

    /**
     * @param Context $context
     * @param GuestOrderLinkerInterface $guestOrderLinker
     * @param AuthSession $authSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly GuestOrderLinkerInterface $guestOrderLinker,
        private readonly AuthSession $authSession,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Link an eligible guest order and return to the order view.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage((string)__('A valid order ID is required.'));
            return $this->redirectToOrders();
        }

        try {
            $adminUser = $this->authSession->getUser();
            $adminUserId = $adminUser ? (int)$adminUser->getId() : null;
            $order = $this->guestOrderLinker->link($orderId, $adminUserId);
            $this->messageManager->addSuccessMessage(
                (string)__(
                    'Order #%1 has been linked to the customer account %2.',
                    $order->getIncrementId(),
                    $order->getCustomerEmail()
                )
            );
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->critical(
                'Guest order linking failed.',
                ['exception' => $exception, 'order_id' => $orderId]
            );
            $this->messageManager->addErrorMessage(
                (string)__('The guest order could not be linked. Review the Magento logs and try again.')
            );
        }

        return $this->redirectToOrder($orderId);
    }

    /**
     * Redirect back to the selected Admin order.
     *
     * @param int $orderId
     * @return Redirect
     */
    private function redirectToOrder(int $orderId): Redirect
    {
        return $this->resultRedirectFactory->create()->setPath(
            'sales/order/view',
            ['order_id' => $orderId]
        );
    }

    /**
     * Redirect to the Admin order grid.
     *
     * @return Redirect
     */
    private function redirectToOrders(): Redirect
    {
        return $this->resultRedirectFactory->create()->setPath('sales/order/index');
    }
}
