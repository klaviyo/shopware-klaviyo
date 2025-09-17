# Change Log

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
