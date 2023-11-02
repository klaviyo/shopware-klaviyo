# 1.4.0
# Fix: Issue where Shopware Users which are doing the Double Opt In in Shopware system were not transfered to Klaviyo but with "NOT SUBSCRIBED".
# Fix: Added selector to Klaviyo List name for subscribers.
# Fix: Now Double-Opt in messaging at configuration page is visible in all configuration scopes/sales channels.

# 1.3.1
# Fix: Resolved the issue where Klaviyo Public API key validation was not working as intended.

# 1.3.0
# New: Cart restore link now fills up the address data that customer has provided before abandoning the cart ( if applicable ).
# New: Added option to select the mapping for their order as well as delivery status as a mandatory field in a dropdown menu so that this status also arrives in Klaviyo.

# 1.2.0
# Feature: Added compatibility with "Consentmanager" from consentmanager.net
# Improvement: Re-factored "checkout started" event implementation in plugin for better compatibility with Checkout customizations and plugins ( like 1 step checkout and others ).
# NOTE: If you have extensive customizations of plugin files at checkout we recommend you review and verify customizations on your side.

# 1.1.3
# Fix: The "Refunded Order" event is now displayed after clicking the "Synchronized historical events" button
# Fix: Resolving the issue where task manager can come to a standstill/stop.

# 1.1.2
# Fix: "Paid Order" event was not displayed for non-paid order after historical synchronization
# Fix: Resolved the issue when events are duplicated in the profile Activity Logs after each historical synchronization
# Fix: Removed dump(extensionData) call method in twig file

# 1.1.1
# Fix: Fixed the issue where some customers may see incorrect dates of the events passed to Klaviyo service ( fulfilled order events etc... ).


# 1.1.0
# Fix: Fixed Klaviyo event sync when tracking checkboxes are unchecked in the admin panel.
# Fix: Correction of historical data order statuses synchronization.
# Fix: Resolved an issue with the "Back in stock" modal.
# New: A new endpoint has been added, thanks to which you can find out the current version of the installed Klaviyo plugin.

# 1.0.19
# Feature: Added ability to change order identification variable that will be sent to the klaviyo ( was before: order hash | now you can choose either: order hash OR order id )

# 1.0.18
# Fix: Fixed the issue where Back In Stock at Product Pate was not sending data to klaviyo

# 1.0.17
* Fix: Fixed the issue where products had incorrect links ( in klaviyo ) to stores of other languages/domains ( shopware ) if there are numerous domains assigned to single sales channel.

# 1.0.16
* New: Added sales channel information to Klaviyo customer 

# 1.0.15
* New: Added compatibility with CookieBot
* New: Added compatibility with the newest versions

#1.0.14
* New: Added variant identifier selection for BIS
 
# 1.0.13
* New: Added tracking for "PAID" orders
* New: Added product SKU in "Notify in stock" functionality

# 1.0.12
* New: Added cart restore functionality

# 1.0.11
* Fix: Context is kept for background processes

# 1.0.10
* Fix: Fixed issue with plugin's localstorage item set without cookie consent

# 1.0.9
* Fix: Fixed issue with Klaviyo script initialization with no cookie consent allowance
* Fix: Fixed issue with "Private API Key" field unsecure displaying

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
