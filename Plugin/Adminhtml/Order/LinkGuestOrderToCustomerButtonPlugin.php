<?php

declare(strict_types=1);

namespace Haroone\LinkGuestOrderToCustomer\Plugin\Adminhtml\Order;

use Haroone\LinkGuestOrderToCustomer\Model\CustomerCandidateResolver;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Adds the linking entry point to eligible Admin order pages.
 */
class LinkGuestOrderToCustomerButtonPlugin
{
    /**
     * @param AuthorizationInterface $authorization
     * @param Escaper $escaper
     * @param CustomerCandidateResolver $customerCandidateResolver
     */
    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly Escaper $escaper,
        private readonly CustomerCandidateResolver $customerCandidateResolver
    ) {
    }

    /**
     * Add the button before the order view block is attached to the layout.
     *
     * @param View $subject
     * @param LayoutInterface $layout
     * @return array{0: LayoutInterface}
     */
    public function beforeSetLayout(View $subject, LayoutInterface $layout): array
    {
        if (!$this->authorization->isAllowed('Haroone_LinkGuestOrderToCustomer::link')) {
            return [$layout];
        }

        $order = $subject->getOrder();
        if (!(int)$order->getEntityId()
            || (int)$order->getCustomerId() > 0
            || !$order->getCustomerIsGuest()
        ) {
            return [$layout];
        }

        try {
            $this->customerCandidateResolver->resolve($order);
        } catch (LocalizedException) {
            return [$layout];
        }

        $url = $subject->getUrl('haroone_linkguestordertocustomer/order/confirm');
        $subject->addButton(
            'haroone_link_guest_order_to_customer',
            [
                'id' => 'haroone-link-guest-order-to-customer',
                'label' => __('Link Guest Order to Customer'),
                'class' => 'link',
                'onclick' => "setLocation('" . $this->escaper->escapeJs($url) . "')",
            ],
            1,
            10
        );

        return [$layout];
    }
}
