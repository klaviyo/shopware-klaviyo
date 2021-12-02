# Klaviyo Business logic related services

## Overview

In general those classes are private and can not be used outside the plugin.
They are responsible for execution of the business logic related to interaction with Klaviyo

### API Client component

#### Namespace
`Klaviyo\Integration\Klaviyo\Client`

#### Overview

In this namespace we have a low level component that is responsible for an interaction with the Klaviyo 
API directly(Using Guzzle REST client). This component is designed to be completely independent from the Shopware 6.
In this case it will be more stable. and we will be able to reuse it in another projects. Except dependencies on the 
tool util, that could be easily separated from the plugin, this component depends only on the Symfony serialization component 
that could be required without other symfony parts

**An entry point to the component functionality is `Klaviyo\Integration\Klaviyo\Client\Client`**

### API Gateway

#### Namespace
`Klaviyo\Integration\Klaviyo\Gateway`

#### Overview

In this namespace we have a bridge between Shopware 6 and Klaviyo API Client component.
Gateway knows about Klaviyo Plugin configuration and how to translate it to the Klaviyo API Client component configuration.
Gateway also knows that there could be configuration overrides in the shopware in case when we use a different sales 
channels. Because of circumstances mentioned above, gateway is responsible for:
* Initialization of the client with the correct configuration
* Granting an explicit interface for working with the Klaviyo API
* Shopware 6 Domain model entities conversion into Klaviyo API DTO
* Proper exception handling and logging

The only thing related to the API that gateway is not responsible for is enabling/disabling of the specific events tracking.
It was done on purpose.

**An entry point to the functionality is `Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway`**

### Catalog Feed

#### Namespace
`Klaviyo\Integration\Klaviyo\CatalogFeed`

#### Overview

In this namespace we have classes related to the catalog feed integration implementation

**An entry point to the functionality is `Klaviyo\Integration\Klaviyo\CatalogFeed\CatalogFeedConstructor`**