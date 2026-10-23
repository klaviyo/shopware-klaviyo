# Change Log
## [4.5.0] - 2026-10-14
### Added
- **Added** optional date range (from/till) for the subscriber historical sync, matching the order sync.
- **Added** the cookie `klaviyo_identified`, a pseudonymous marker (a hash of the customer or subscriber id) that tells the storefront whether an identity can be loaded. It is only set after Klaviyo cookie consent and is listed in the "Klaviyo Cookies" consent group. Shops using Shopware's consent banner show it once more to returning visitors after the update.
### Changed
- **Changed** the admin settings page into tabs: Credentials, Cookie consent, Tracking, Mapping, Historical sync, Scheduled Sync and Back In Stock.
- **Changed** the Save button to explain via tooltip why it is disabled (missing credentials or invalid field mapping).
- **Changed** the historical sync controls to live in the Historical sync tab instead of the page header.
- **Changed** newsletter subscribers to be identified only after confirming their subscription. Existing `klaviyo_subscriber` cookies are treated as anonymous until the next confirmation. The subscriber marker is only created at confirmation: if it is removed later (the visitor navigates on before giving consent, or declines and later re-accepts), the subscriber stays anonymous until the next confirmation.
- **Changed** the "Klaviyo Cookies" consent description.
- **Changed** the "Export in Klaviyo Format" buttons on the promotion list and detail pages to download the CSV through the authenticated admin API instead of a plain browser navigation. A failed download now shows an error notification.

### Fixed
- **Fixed** historical sync date range excluding records on the last selected day.
- **Fixed** customer and newsletter subscriber data (email, name, address) being rendered into cacheable storefront HTML. The identity is now loaded client-side from a dedicated no-store endpoint (`/account/klaviyo/identity`).
- **Fixed** hardened the promotion code export: it is now an authenticated admin API call that requires the `promotion:read` privilege. Integrations exporting codes need that privilege from this version on.
### Removed
- **Removed** the Twig blocks `klaviyo_integration_settings_content_sales_channel_customer`, `klaviyo_integration_settings_content_sales_channel_order` and `klaviyo_integration_settings_actions_historical_events_synchronization_control` from the admin settings page template. Their content now lives in the Mapping and Historical sync tabs of `klaviyo-integration-settings-general`; third-party overrides of these blocks must be moved to the tab templates.
- **Removed** the unused admin template `sw-promotion-list-override/sw-promotion-list.html.twig` (targeted the old `sw-promotion-list` component and was never loaded).

## [4.4.0] - 2026-08-26
### Fixed
- **Fixed** Bug in Order Partially Shipped & Partially Paid event
- **Fixed** Prevent automatic sync customers on plugin installation

### Removed
- **Removed** automatic daily subscriber sync

## [4.3.0] - 2026-06-09

### Fixed
- **Fixed** the Back In Stock field visibility and inheritance issues.

### Changed
- **Changed** the API revision to revision `2026-01-15`.

### Removed
- **Removed** cookie consent requirement for the `Started Checkout` event.
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
