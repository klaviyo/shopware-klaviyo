# 2.13.2
# Fix: Fixed an error when the SwitchBuyBoxVariantEvent event was triggered on widget. Fixing dependencies in the template.

# 2.13.1
# Fix: Fixed an issue with missing List ID of subscribers on PDP. Redesigned saving List ID of subscribers in the plugin settings.
# Fix: Fixed an error when the SwitchBuyBoxVariantEvent event was triggered on the PDP.

# 2.13.0
# New: The API revision has been updated to the latest version.
# New: To optimize work and better performance, a separate Cron task was added to synchronize excluded subscribers.
# Fix: Fixed work of filters in Klaviyo Job Listing.

# New: The back in stock pop up is now available when using the `frontend.cms.buybox.switch` route (SwitchBuyBoxVariantEvent).
# Fix: Fixed an error when applying promotions. 
# Fix: Fixed a bug when synchronizing customer data.
# Fix: Fixed a bug with the display of empty jobs after daily synchronization.

# 2.11.1
# Fix: Outdated code tests that caused errors have been removed.
# Fix: Errors when synchronizing historical data have been fixed.
# Fix: Fixed an error with the lack of translation of the product manufacturer.
# Fix: Fixed a bug with transferring data about a customer group to custom fields.
# Fix: Fixed behavior of the status of the parent job in Klaviyo Job Listing when child jobs are still in progress.
# Fix: Fixed displaying the number of records in Klaviyo Job Listing when loading. Improved listing performance.
# Fix: Fixed a bug related to the "Add to Cart" event when the event was not triggered.

# 2.11.0
# Fix: Fixed a bug in updating customer custom fields.
# Fix: Fixed a bug with exceeding the request limit rate in Klaviyo.
# Fix: Fixed an error in receiving a response from Klaviyo after creating a customer.
# Fix: Fixed transmission of customer's first name, last name and salutation when full synchronizing customers.
# New: Added synchronization of new events of order statuses such as: Partially Paid Order and Partially Shipped Order.

# 2.10.0
# Fix: Added validation of phone numbers in order events. If the phone number does not exist or does not comply with the e.164 standard, the event is posted in Klaviyo without transmitting the incorrect phone number.
# Fix: Correction of some titles.
# New: Added shipping costs missing in variables and data.

# 2.9.0
# Fix: Visibility of Back In Stock on PDP.
# New: The plugin API has been updated to the latest version of the Klaviyo API.

# 2.8.4
# Fix: The category is now displayed after adding a product from a dynamic product group.
# Fix: Plugin provides the correct product URL by domain language.
# Fix: Changing titles in the payload Add To Cart event.

# 2.8.3
# Fix: The add to cart event is now triggered in real time mode, and not according to a schedule
# Fix: Fixed a bug when performing asynchronous subscription operations.


# 2.8.2
# Fix: Fixed an issue where some product related event was missing product name
# Fix: Fixed an issue where some transaction data was not synced
# Fix: Fixed an issue where some data was missing for cart related events

# 2.8.1
# Fix: Added customer language field that will be sent to Klaviyo upon other data when syncing newsletter recipients.
# Fix: Fixed typos

# 2.8.0
# New: Added better logging across the plugin.
# Fix: Fixed the issue where customers were unable to select promotions in the admin panel grid for export.
# Fix: Fixed an issue where some customers may encounter when purchased/placed orders haven't been updated properly.
# Fix: Fixed an issue where email opt-in banner was missing at Storefront

# 2.7.1
# Fix: Fixed an error/issue with Cookiebot that was thrown in browser console when "Use Default Cookie Notification" was set to yes.
# Fix: Fixed an issue where Order ID was displayed incorrectly ( Order ID was displayed instead of Order Number even though it was set to Order Number in plugin configuration ) in the refunded order Events.

# 2.7.0
# Fix: Added fixes for stable work with cookies manager - CookieBot.
# Fix: Added fixes when synchronizing subscribers.
# Feature: The 'Daily Subscribers Synchronization' configuration is added.
# Feature: The 'Enable cleanup of old jobs' configuration is added.

# 2.6.0
# Feature: Now Double-Opt in messaging at configuration page is visible in all configuration scopes/sales channels.

# 2.5.2
# Fix: Fixed the issue where selector was not displayed in the "Klaviyo List Name for subscribers" configuration.

# 2.5.1
# Fix: Resolved the issue where Klaviyo Public API key validation was not working as intended.

# 2.5.0
# New: Added option to select the mapping for their order as well as delivery status as a mandatory field in a dropdown menu so that this status also arrives in Klaviyo.

# 2.4.0
# Feature: Added compatibility with "Consentmanager" from consentmanager.net
# Improvement: Re-factored "checkout started" event implementation in plugin for better compatibility with Checkout customizations and plugins ( like 1 step checkout and others ).
# NOTE: If you have extensive customizations of plugin files at checkout we recommend you review and verify customizations on your side.
# Fix: Fixed the issue where "Unsubscribe" in My Account page was not working.

# 2.3.2
# Fix: The "Refunded Order" event is now displayed after clicking the "Synchronized historical events" button
# Fix: Resolving the issue where task manager can come to a standstill/stop.

# 2.3.1
# Fix: "Ordered Product" event order after historical synchronization

# 2.3.0
# Fix: "Paid Order" event was not displayed for non-paid order after historical synchronization
# Fix: Resolved the issue when events are duplicated in the profile Activity Logs after each historical synchronization
# Fix: Removed dump(extensionData) call method in twig file
# Fix: When adding "real time" users to Klaviyo "Subscribe to List" api is now used
# Fix: "Subscribers list" admin configuration option is not a select/dropdown with values pulled from Klaviyo service ( if api credentials are valid )

# 2.2.0
# New: Cart restore link now fills up the address data that customer has provided before abandoning the cart ( if applicable ). 
# Fix: Fixed the issue where some customers may see incorrect dates of the events passed to Klaviyo service ( fulfilled order events etc... ).

# 2.1.0
# Fix: Fixed Klaviyo event sync when tracking checkboxes are unchecked in the admin panel.
# Fix: Correction of historical data order statuses synchronization.
# Fix: Resolved an issue with the "Back in stock" modal.
# New: A new endpoint has been added, thanks to which you can find out the current version of the installed Klaviyo plugin.

# 2.0.1
# Fix: Resolved the issue with error message upon subscription to newsletter in some cases.

# 2.0.0
# Compatibility release with shopwrae 6.5^
# Fix: Replaced usage of removed classes & files.
# Fix: Minor changes to extension configuration classes/templates ( at extension configuration page ).
# New: Job Scheduler Update - implemented compatibility with Shopawre 6.5^ versions.
# New: Job Scheduler Update - Job scheduler handlers now do extend recommended interfaces. 
# New: Controller routes now have annotation declaration in new format.
# New: Some changes that was made do make the extension backward-incompatible. You can see the dependencies in composer.json file.

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
