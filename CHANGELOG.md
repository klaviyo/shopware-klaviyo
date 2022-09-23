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
