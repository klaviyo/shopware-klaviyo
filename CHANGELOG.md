# Change Log
## [2.21.0] - 2025-10-30

### Added
- **Added** missing customer data properties (e.g., company, title and extra address line) to the customer sync process for **full data integrity**.
- **Added** the plugin version number to all API request headers to **aid debugging and support** on external services.

### Fixed
- **Fixed** a critical bug that caused the creation of **duplicate customer profiles** during data synchronization.
- **Fixed** an issue where product categories were incorrectly sent as a single string; they are now correctly sent as a **list/array** to meet API requirements.

## [2.20.0] - 2025-09-11

### Added
- Add missing cookie consent validation to events

### Fixed
- Fix translation in plugin
- Fix Boolean Customer property not synced
- Fix custom field handling for Order Events
- Fix warning in FullCustomerOrderSyncOperation
- Fix subscription through Back In Stock form
- Fix sales channel configuration validation
- Fix customer address in Order events

## [2.19.0] - 2025-07-10
This release significantly improves efficiency by reducing redundant Klaviyo API calls during sync operations, and it prevents the dispatch of multiple Double Opt-In emails to customers.

### Fixed
- Resolved an issue causing an infinite loop in sync tasks.


## [2.18.0] - 2025-07-01

This release introduces several changes to enhance the functionality and maintainability of the Klaviyo Shopware integration. The most notable updates include the removal of the dependency on `od/sw6-job-scheduler`, the addition of date-based filtering capabilities, and improvements to serializers for handling customer data.

### Added
- Add date-based filtering for historical event tracking synchronization.

### Changed

- Integrate the job scheduler in Klaviyo plugin.
- Replace text field for `Klaviyo List ID` with a select field in the configuration.

### Fixed

- Fix birthday and custom fields for Customer Profile sync.
- Fix onsite script loading issue.
- Fix for invalid email address stuck in the queue.
- Fix Customer Group translation issue.
- Fix abandoned cart restoration issue.
- Improvements for Event Tracking synchronization.
- Fix order date handling in Order Sync.
