<?php
declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class CheckoutRegisterPageLoadedEventListener implements EventSubscriberInterface
{
    private const STOREFRONT_VIEW_NAME = '@Storefront/storefront/page/checkout/address/index.html.twig';

    private EntityRepository $customerRepository;

    public function __construct(
        EntityRepository $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'addCustomerAddressDataToPage'
        ];
    }

    public function addCustomerAddressDataToPage(StorefrontRenderEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        $customerId = $session->get('customerId');

        if (($event->getView() === self::STOREFRONT_VIEW_NAME) &&
            isset($event->getParameters()['data']) &&
            $customerId
        ) {
            $criteria = new Criteria();
            $criteria->addAssociation('addresses');
            $criteria->addAssociation('defaultBillingAddress');
            $criteria->addAssociation('defaultShippingAddress');
            $criteria->addFilter(new EqualsFilter('id', $customerId));

            /** @var CustomerEntity|null $customer */
            $customer = $this->customerRepository->search($criteria, $event->getContext())->first();

            if ($customer !== null) {
                $customerShippingAddress = $customer->getDefaultShippingAddress();
                $customerBillingAddress = $customer->getDefaultBillingAddress();

                $customerShippingAddress->assign(['accountType' => null]);
                $customerBillingAddress->assign(['accountType' => null]);

                $eventData = $event->getParameters()['data'];

                $eventData->set('salutationId', $customerBillingAddress->getSalutationId());
                $eventData->set('firstName', $customerBillingAddress->getFirstName());
                $eventData->set('lastName', $customerBillingAddress->getLastName());
                $eventData->set('email', $customer->getEmail());
                $eventData->set('shippingAddress', $customerShippingAddress);
                $eventData->set('billingAddress', $customerBillingAddress);

                $session->set('customerId', null);
            }
        }
    }
}