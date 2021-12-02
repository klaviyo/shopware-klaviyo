Capabilities and Restrictions
==============================
***

At this moment plugin provides next integrations with Klaviyo:

* Integration with the Klaviyo events tracking system
* Integration with Klaviyo profiles

### Integration with the Klaviyo events tracking system

#### Overview

Plugin is responsible to track next user events:
* Active on Site
* Viewed Product
* Added to Cart
* Started Checkout
* Placed Order
* Ordered Product
* Fulfilled Order 
* Cancelled Order
* Refunded Order

Plugin is also responsible for the synchronization of historical events. If you want to synchronize historical events 
with the klaviyo, you could open the plugin configuration page and press "Schedule Synchronization" button at the top
of the configs list.

see more details in klaviyo [documentation](https://help.klaviyo.com/hc/en-us/articles/115005082927-Integrate-a-Custom-Ecommerce-Cart-or-Platform)

##### Restrictions:
* "Added to Cart" event will be send only for the authenticated customer
* "Active on Site" and "Viewed Product" events are triggered from the storefront, so for now only default "Storefront"
is supported
* "Placed Order" event will be triggered when customer will finish checkout process, orders created from the 
administration panel will also trigger this event
* "Ordered Product" event will be triggered when customer will finish checkout process, orders created from the
administration panel will also trigger this event
* "Fulfilled Order" event will be sent when Order status will be changed to "Done". Other statuses like "payment status"
or "shipping status" are ignored in current implementation
* "Cancelled Order" event will be sent when Order status will be changed to "Canceled". Other statuses like "payment status"
  or "shipping status" are ignored in current implementation
* "Refunded Order" event will be sent when Order payment status will be changed to "Refunded". Other statuses like "order status"
  or "shipping status" are ignored in current implementation

### Integration with Klaviyo profiles
#### Overview

Plugin is responsible for the synchronization of the Shopware 6 Newsletter Recipients(I will call them Subscribers in the future)
and Klaviyo profiles. Subscribers will be sent to the Klaviyo using emails as and identifier 

#### Restrictions:
* Synchronization is possible only with the single Klaviyo Profiles List. 
You should define the list name in the plugin configuration right after the installation 
* Synchronization was implemented only one way from Shopware 6 to Klaviyo, so any changes you made in Klaviyo list 
defined in the plugin configuration will be overridden
* Synchronization will be made automatically once per day at time defined in the configuration, but could be triggerred manually
* Synchronization time setting can not be overridden for sales channels, only system level configuration will be used. Other settings can be overridden
* Subscribers will be sent to the Klaviyo only if they are active, so inactive subscribers will be ignored