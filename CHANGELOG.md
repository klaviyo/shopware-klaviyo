# Change Log

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
