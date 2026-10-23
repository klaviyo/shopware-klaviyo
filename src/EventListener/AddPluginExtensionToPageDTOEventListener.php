<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Configuration\Configuration;
use Klaviyo\Integration\Entity\Helper\NewsletterSubscriberHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\Translator;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CustomerPropertiesTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\NewsletterSubscriberPropertiesTranslator;
use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AddPluginExtensionToPageDTOEventListener implements EventSubscriberInterface
{
    public const PDP_EXTENSION = 'klaviyoIntegrationPluginExtension';

    private GetValidChannelConfig $getValidChannelConfig;
    /**
     * @deprecated unused, identity moved to CustomerIdentityProvider; will be removed in a future release
     */
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private Translator\ProductTranslator $productTranslator;
    private Translator\StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator;
    private LoggerInterface $logger;
    /**
     * @deprecated unused, will be removed in a future release
     */
    private NewsletterSubscriberHelper $newsletterSubscriberHelper;
    /**
     * @deprecated unused, will be removed in a future release
     */
    private RequestStack $requestStack;
    /**
     * @deprecated unused, will be removed in a future release
     */
    private NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator;
    private KlaviyoGateway $klaviyoGateway;

    /**
     * @deprecated $customerPropertiesTranslator, $newsletterSubscriberHelper, $requestStack and
     * $newsletterSubscriberPropertiesTranslator are unused and will be removed in a future release.
     */
    public function __construct(
        GetValidChannelConfig $getValidChannelConfig,
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        Translator\ProductTranslator $productTranslator,
        Translator\StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator,
        LoggerInterface $logger,
        NewsletterSubscriberHelper $newsletterSubscriberHelper,
        RequestStack $requestStack,
        NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator,
        KlaviyoGateway $klaviyoGateway,
    ) {
        $this->getValidChannelConfig = $getValidChannelConfig;
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
        $this->productTranslator = $productTranslator;
        $this->startedCheckoutEventTrackingRequestTranslator = $startedCheckoutEventTrackingRequestTranslator;
        $this->logger = $logger;
        $this->newsletterSubscriberHelper = $newsletterSubscriberHelper;
        $this->requestStack = $requestStack;
        $this->newsletterSubscriberPropertiesTranslator = $newsletterSubscriberPropertiesTranslator;
        $this->klaviyoGateway = $klaviyoGateway;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onPageLoaded',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            SwitchBuyBoxVariantEvent::class => 'onSwitchOptionLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutPageLoaded',
        ];
    }

    public function onPageLoaded(GenericPageLoadedEvent $event): void
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            $configuration = $this->getValidChannelConfig->execute($salesChannelContext->getSalesChannel()->getId());

            if (null === $configuration) {
                return;
            }

            $event->getPage()->addExtension(self::PDP_EXTENSION, new ArrayStruct([
                'configuration' => $configuration,
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

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
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
                'listId' => $configuration->getSubscribersListId(),
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

    public function onSwitchOptionLoaded(SwitchBuyBoxVariantEvent $event): void
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            $configuration = $this->getValidChannelConfig->execute($salesChannelContext->getSalesChannel()->getId());

            if (null === $configuration) {
                return;
            }

            $event->getSalesChannelContext()->addExtension(self::PDP_EXTENSION, new ArrayStruct([
                'configuration' => $configuration,
            ]));

            $extensionData = $event->getSalesChannelContext()->getExtension(self::PDP_EXTENSION);
            /** @var Configuration $configuration */
            $configuration = $extensionData['configuration'];
            $extensionData['productInfo'] = $this->productTranslator->translateToProductInfo(
                $event->getContext(),
                $event->getSalesChannelContext(),
                $event->getProduct()
            );
            $extensionData['backInStockData'] = [
                'listId' => $configuration->getSubscribersListId(),
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
                'Could not track Checkout started event after checkout page loaded',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }
}
