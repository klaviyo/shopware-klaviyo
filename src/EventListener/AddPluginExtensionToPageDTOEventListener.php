<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Configuration\Configuration;
use Klaviyo\Integration\Entity\Helper\NewsletterSubscriberHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\Translator;
use Klaviyo\Integration\Klaviyo\Gateway\GetListIdByListNameInterface;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CustomerPropertiesTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\NewsletterSubscriberPropertiesTranslator;
use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AddPluginExtensionToPageDTOEventListener implements EventSubscriberInterface
{
    public const PDP_EXTENSION = 'klaviyoIntegrationPluginExtension';

    private GetValidChannelConfig $getValidChannelConfig;
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private Translator\ProductTranslator $productTranslator;
    private Translator\StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator;
    private LoggerInterface $logger;
    private NewsletterSubscriberHelper $newsletterSubscriberHelper;
    private RequestStack $requestStack;
    private NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator;
    private GetListIdByListNameInterface $getListIdByListName;
    private KlaviyoGateway $klaviyoGateway;

    // TODO: make some args as proxy
    public function __construct(
        GetValidChannelConfig $getValidChannelConfig,
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        Translator\ProductTranslator $productTranslator,
        Translator\StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator,
        LoggerInterface $logger,
        NewsletterSubscriberHelper $newsletterSubscriberHelper,
        RequestStack $requestStack,
        NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator,
        GetListIdByListNameInterface $getListIdByListName,
        KlaviyoGateway $klaviyoGateway
    ) {
        $this->getValidChannelConfig = $getValidChannelConfig;
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
        $this->productTranslator = $productTranslator;
        $this->startedCheckoutEventTrackingRequestTranslator = $startedCheckoutEventTrackingRequestTranslator;
        $this->logger = $logger;
        $this->newsletterSubscriberHelper = $newsletterSubscriberHelper;
        $this->requestStack = $requestStack;
        $this->newsletterSubscriberPropertiesTranslator = $newsletterSubscriberPropertiesTranslator;
        $this->getListIdByListName = $getListIdByListName;
        $this->klaviyoGateway = $klaviyoGateway;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onPageLoaded',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutPageLoaded',
        ];
    }

    public function onPageLoaded(GenericPageLoadedEvent $event)
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            $configuration = $this->getValidChannelConfig->execute($salesChannelContext->getSalesChannel()->getId());
            if (null === $configuration) {
                return;
            }

            $event->getPage()->addExtension(self::PDP_EXTENSION, new ArrayStruct([
                'configuration' => $configuration,
                'customerIdentity' => $this->getCustomerIdentity($salesChannelContext),
            ]));
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Could not add Klaviyo plugin extension to the page object, reason: %s',
                    $throwable->getMessage()
                ),
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        try {
            if (!$event->getPage()->hasExtension(self::PDP_EXTENSION)) {
                return;
            }

            $extensionData = $event->getPage()->getExtension(self::PDP_EXTENSION);
            /** @var Configuration $configuration */
            $configuration = $extensionData['configuration'];
            $extensionData['productInfo'] = $this->productTranslator->translateToProductInfo(
                $event->getContext(),
                $event->getSalesChannelContext(),
                $event->getPage()->getProduct()
            );
            $extensionData['backInStockData'] = [
                'listId' => $this->getListIdByListName->execute(
                    $event->getSalesChannelContext()->getSalesChannel(),
                    $configuration->getSubscribersListName()
                ),
            ];
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Could not add Klaviyo plugin extension product information to the page object, reason: %s',
                    $throwable->getMessage()
                ),
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    public function onCheckoutPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $config = $this->getValidChannelConfig->execute($event->getSalesChannelContext()->getSalesChannelId());

        if (null === $config || !$config->isTrackStartedCheckout()) {
            return;
        }

        if (!$event->getPage()->hasExtension(self::PDP_EXTENSION)) {
            return;
        }

        try {
            $context = $event->getSalesChannelContext();
            $salesChannelContext = $event->getSalesChannelContext();
            $cart = $event->getPage()->getCart();

            $eventDTO = $this->startedCheckoutEventTrackingRequestTranslator->translate($context, $cart);
            $this->klaviyoGateway->trackStartedCheckoutRequests($salesChannelContext->getSalesChannelId(), [$eventDTO]);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Could not track Checkout started event after the item qty updated',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    private function getCustomerIdentity(SalesChannelContext $channelContext)
    {
        $subscriberId = $this->requestStack->getCurrentRequest()->cookies->get('klaviyo_subscriber') ?? null;
        $customerIdentity = null;

        if ($customer = $channelContext->getCustomer()) {
            $customerIdentity = $this->customerPropertiesTranslator->translateCustomer(
                $channelContext->getContext(),
                $customer
            );
        } elseif ($subscriberId) {
            $subscriber = $this->newsletterSubscriberHelper->getSubscriber(
                $subscriberId,
                $channelContext->getContext()
            );

            if (null !== $subscriber) {
                $customerIdentity = $this->newsletterSubscriberPropertiesTranslator->translateSubscriber($subscriber);
            }
        }

        return $customerIdentity;
    }
}
