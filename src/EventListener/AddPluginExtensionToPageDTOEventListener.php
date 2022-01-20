<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Configuration\Configuration;
use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\FrontendApi\Translator\ProductTranslator;
use Klaviyo\Integration\Klaviyo\FrontendApi\Translator\StartedCheckoutEventTrackingRequestTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CustomerPropertiesTranslator;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddPluginExtensionToPageDTOEventListener implements EventSubscriberInterface
{
    public const KLAVIYO_INTEGRATION_PLUGIN_EXTENSION = 'klaviyoIntegrationPluginExtension';

    private ConfigurationRegistry $configurationRegistry;
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private ProductTranslator $productTranslator;
    private StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator;
    private LoggerInterface $logger;

    public function __construct(
        ConfigurationRegistry $configurationRegistry,
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        ProductTranslator $productTranslator,
        StartedCheckoutEventTrackingRequestTranslator $startedCheckoutEventTrackingRequestTranslator,
        LoggerInterface $logger
    ) {
        $this->configurationRegistry = $configurationRegistry;
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
        $this->productTranslator = $productTranslator;
        $this->startedCheckoutEventTrackingRequestTranslator = $startedCheckoutEventTrackingRequestTranslator;
        $this->logger = $logger;
    }

    public function onPageLoaded(GenericPageLoadedEvent $event)
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();

            /** @var Configuration $configuration */
            $configuration = $this->configurationRegistry
                ->getConfiguration($salesChannelContext->getSalesChannel()->getId());

            $page = $event->getPage();

            $customer = $event->getSalesChannelContext()->getCustomer();

            $customerIdentity = null;
            if ($customer) {
                $customerIdentity = $this->customerPropertiesTranslator
                    ->translateCustomer($event->getContext(), $customer);
            }

            $extensionData = new ArrayStruct(
                [
                    'configuration' => $configuration,
                    'customerIdentity' => $customerIdentity
                ]
            );

            $page->addExtension(
                self::KLAVIYO_INTEGRATION_PLUGIN_EXTENSION,
                $extensionData
            );
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf(
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
            $page = $event->getPage();
            if ($page->hasExtension(self::KLAVIYO_INTEGRATION_PLUGIN_EXTENSION)) {
                $extensionData = $page->getExtension(self::KLAVIYO_INTEGRATION_PLUGIN_EXTENSION);
            } else {
                $extensionData = new ArrayStruct([]);
            }

            $extensionData['productInfo'] = $this->productTranslator
                ->translateToProductInfo($event->getContext(), $page->getProduct());
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf(
                    'Could not add Klaviyo plugin extension product information to the page object, reason: %s',
                    $throwable->getMessage()
                ),
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    public function onCheckoutPageLoaded(CheckoutConfirmPageLoadedEvent $confirmPageLoadedEvent)
    {
        try {
            $context = $confirmPageLoadedEvent->getSalesChannelContext();
            $cart = $confirmPageLoadedEvent->getPage()->getCart();

            $page = $confirmPageLoadedEvent->getPage();
            if ($page->hasExtension(self::KLAVIYO_INTEGRATION_PLUGIN_EXTENSION)) {
                $extensionData = $page->getExtension(self::KLAVIYO_INTEGRATION_PLUGIN_EXTENSION);
            } else {
                $extensionData = new ArrayStruct([]);
            }

            $eventDTO = $this->startedCheckoutEventTrackingRequestTranslator->translate($context, $cart);
            $extensionData['startedCheckoutEventTrackingRequest'] = $eventDTO;
        } catch (\Throwable $throwable) {
            $this->logger
                ->error(
                    'Could not track Checkout started event after the item qty updated',
                    ContextHelper::createContextFromException($throwable)
                );
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GenericPageLoadedEvent::class => 'onPageLoaded',
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutPageLoaded'
        ];
    }
}
