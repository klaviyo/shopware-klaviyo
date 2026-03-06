# Change Log

## [4.2.0] - 2026-02-05

### Fixed
- **Fixed** Prevent duplicate products from Abandoned cart mail
- **Fixed** Prevent uncoupling line items after restoring order
- **Fixed** Fix cookie consent handling
- **Fixed** Fix inheritance option for boolean configurations

## [4.1.0] - 2025-10-30

### Added
- **Added** missing customer data properties (e.g., company, title and extra address line) to the customer sync process for **full data integrity**.
- **Added** the plugin version number to all API request headers to **aid debugging and support** on external services.

### Fixed
- **Fixed** a critical bug that caused the creation of **duplicate customer profiles** during data synchronization.
- **Fixed** an issue where product categories were incorrectly sent as a single string; they are now correctly sent as a **list/array** to meet API requirements.

## [4.0.1] - 2025-09-11

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

## [4.0.0] - 2025-07-16
This release introduces **date-based filtering** for historical event tracking, significantly **reduces redundant Klaviyo API calls** for improved efficiency, and **removes the external `od/sw6-job-scheduler` dependency**, integrating its functionality directly into the plugin.

### Added
- Added **date-based filtering** for historical event tracking synchronization, allowing more precise data management.

### Changed
- The job scheduler functionality is now **directly integrated into the Klaviyo plugin**, removing the external `od/sw6-job-scheduler` dependency.
- Replaced the text input field for **Klaviyo List ID** with a more convenient **select field** in the configuration settings.

### Fixed

- Resolved an issue where the **onsite script wasn't reliably loading** in the storefront. Users manually loading the script should verify their setup to prevent duplication and conflicts.
- Fixed an issue causing an **infinite loop** in sync tasks, improving stability.
- Corrected issues with **birthday and custom fields** for Customer Profile synchronization, ensuring accurate data.
- Addressed an issue preventing **invalid email addresses** from being properly cleared from the queue.
- Fixed a **Customer Group translation issue**, ensuring correct display across languages.
- Resolved an issue preventing **abandoned carts** from being properly restored.
- Improved **Event Tracking synchronization** reliability and performance.
- Corrected **order date handling** in Order Sync, ensuring accurate historical order data.
