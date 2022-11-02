# 1.0.8
* New: Added new Klviyo brand icons
* New: Added new feature to enable/disable synchronization of deleted accounts' order events
* New: Added job listing cleanup mechanism
* New: Added additional information messages during background job processing
* New: Job Scheduler Update - added job message correct sort order
* Fix: Improved plugin uninstallation process
* Fix: Fixed possible issue with Klaviyo list ID caching 
* Fix: Fixed possible issue with unsubscribed recipients sync from Klaviyo
* Fix: Fixed possible issue with order line items background processing
* Fix: Fixed possible issue with not existing plugin configuration during order synchronization

# 1.0.7
* New: Added api key validation in Klaviyo config
* New: Added toggling the Klaviyo tracking on and off by cookies
* New: Cleanup pending jobs during uninstall process
* New: Changed icon and name of Klaviyo plugin
* Fix: Removed limitations for sales channel options in Klaviyo config
* Fix: Added translations for all Klaviyo texts

# 1.0.6
 * Fix: We fixed issue with order empty delivery
 * Fix: We fixed issue with checkout tracker categories
 * Fix: We fixed issue with feed generation, when there is no cover picture

# 1.0.5
 * New: Now product manufacturer is being transferred to all product-related Klaviyo events.
 * New: Now Klaviyo account credentials could be configured on sales channel level only.
 * New: Now sales channel Klaviyo account can be disabled to prevent any event processing on associated channel.
 * Fix: We fixed issue with storefront event tracking with A/B testing feature enabled in Klaviyo.
 * Fix: We fixed possible issue with customer data sync.
 * Fix: We fixed possible issue with event processing on channels with wrong credentials/configurations.
 * Fix: We fixed issue with error during order status change via Admin UI.
 * Fix: We fixed issue with historical sync of orders with deleted products.

# 1.0.4
 * New: Job Scheduler Update - enhanced Admin UI and handy message handling.
 * New: We updated Klaviyo Person API workflow.
 * Fix: Fixed issue with processing guest orders.
 * Fix: Fixed issue with the "localhost" product link on tracked order events.
 * Fix: Fixed issue with the Klaviyo Tracking JS on pages with a custom layout.
 * Fix: Removed unnecessary "Catalog Feed Products Count" setting from plugin configuration

# 1.0.3
 * Added new feature "Back-in-stock" email notification.
 * Added new feature "Bidirectional (un)subscriber synchronization". Now plugin can synchronize newsletter unsubscribers from the Klaviyo to Shopware and vice versa.

# 1.0.2
 * Performance improvements. Plugin code-base refactoring. Added system job scheduler bundle.

# 1.0.1
 * Unit and Integration test added.

# 1.0.0
 * Basic plugin functionality implementation.
