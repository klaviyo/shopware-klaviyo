# Shopware native behaviour extensions
***

### AddedToCartEventListener

This event listener is responsible for calling use case interactor in order to track "Added to Cart" event

### AddPluginExtensionToPageDTOEventListener

This event listener is responsible for construction data required for storefront twig templates in order to properly setup 
Klaviyo storefront components, which are responsible for the tracking of the next events: "Active On Site", "Viewed Product"

### CheckoutOrderPlacedEventListener

This event listener is responsible for calling use case interactor in order to track "Placed Order" and 
"Ordered Product" events. Event listener should be triggerred when order is created

### OrderStateChangedEventListener

This event listener is responsible for calling use case interactor in order to track next events: 
* "Fulfilled Order"
* "Cancelled Order"
* "Refunded Order"

Event listener should be triggerred when order workflow step(status) was changed or order payment 
workflow step(status) was changed 