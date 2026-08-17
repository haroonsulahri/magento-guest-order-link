<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Controller\Adminhtml\Order;

use Haroone\LinkGuestOrderToCustomer\Block\Adminhtml\Order\Confirm as ConfirmBlock;
use Haroone\LinkGuestOrderToCustomer\Model\CustomerCandidateResolver;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as BackendResultPage;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Displays a server-validated confirmation page before assignment.
 */
class Confirm extends Action
{
    public const ADMIN_RESOURCE = 'Haroone_LinkGuestOrderToCustomer::link';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerCandidateResolver $candidateResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerCandidateResolver $candidateResolver,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Render the confirmation page for an eligible order.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if ($orderId <= 0) {
            $this->messageManager->addErrorMessage((string)__('A valid order ID is required.'));
            return $this->redirectToOrders();
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $customer = $this->candidateResolver->resolve($order);
            /** @var BackendResultPage $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Magento_Sales::sales_order');
            $resultPage->getConfig()->getTitle()->prepend((string)__('Confirm Link to Customer Account'));
            $resultPage->getConfig()->getTitle()->prepend(
                (string)__('Order #%1', $order->getIncrementId())
            );

            $block = $resultPage->getLayout()->getBlock('haroone.linkguestordertocustomer.confirm');
            if (!$block instanceof ConfirmBlock) {
                throw new LocalizedException(__('The confirmation page could not be prepared.'));
            }
            $block->setOrder($order);
            $block->setCustomer($customer);

            return $resultPage;
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (Throwable) {
            $this->logger->critical(
                'Could not prepare the guest order link confirmation page.',
                ['order_id' => $orderId]
            );
            $this->messageManager->addErrorMessage(
                (string)__('The confirmation page could not be loaded. Return to the order and try again.')
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
