# Newsletter language sync to Klaviyo

This document is the **product/engineering spec** for syncing `language` on Klaviyo profiles for **newsletter recipients** (not only registered customers). Use it for **GitHub Issues**, **draft PRs**, and handoff to external developers.

## Problem

- **Registered customers:** language is derived from `CustomerEntity::getLanguageId()` and sent to Klaviyo as profile `properties.language` (via `LocaleCodeProducer` and `CustomerPropertiesTranslator`).
- **Newsletter recipients:** the integration previously built minimal `CustomerProperties` and list payloads without locale, so Klaviyo often had no `language` for email-only subscribers.

Shopware core stores `language_id` on `newsletter_recipient` (required field). The gap was in this plugin’s translators and list API serializers.

## Solution (implemented in this plugin)

1. **Events / storefront identity (cookie subscriber)**  
   `NewsletterSubscriberPropertiesTranslator` injects `LocaleCodeProducer`, takes `Context`, and sets `CustomerProperties` `localeCode` from `NewsletterRecipientEntity::getLanguageId()`.

2. **Bulk / realtime list subscribe jobs**  
   `ProfileContactInfo` includes an optional `localeCode`. Subscriber translators resolve it via `LocaleCodeProducer::getOptionalLocaleCodeForLanguage()` and pass it through. Request normalizers add `attributes.properties.language` (or `properties` on the flat subscribe shape) when present.

3. **Gateway**  
   `KlaviyoGateway::subscribeToKlaviyoList` uses the same parameter order as `addToKlaviyoProfilesList`: `(SalesChannelEntity, Context, NewsletterRecipientCollection, listId)`.

4. **Unsubscribe**  
   `ProfileContactInfo` for opt-out rows does not need locale; `localeCode` remains unset.

5. **DI**  
   `Resources/config/services/gateway_services.xml` wires `LocaleCodeProducer` into the newsletter-related translators.

## Files touched (reference)

| Area | Path |
|------|------|
| Newsletter → `CustomerProperties` | `src/Klaviyo/Gateway/Translator/NewsletterSubscriberPropertiesTranslator.php` |
| List DTOs | `src/Klaviyo/Client/ApiTransfer/Message/Profiles/Common/ProfileContactInfo.php` |
| List translators | `src/Klaviyo/Gateway/Translator/SubscribersToKlaviyoRequestsTranslator.php`, `RealSubscribersToKlaviyoRequestsTranslator.php` |
| Gateway | `src/Klaviyo/Gateway/KlaviyoGateway.php` |
| Jobs | `src/Model/UseCase/Operation/SubscriberSyncOperation.php` |
| Storefront | `src/Klaviyo/Gateway/Translator/CartEventRequestTranslator.php`, `src/EventListener/AddPluginExtensionToPageDTOEventListener.php` |
| HTTP serializers | `src/Klaviyo/Client/Serializer/Normalizer/AddProfilesToListRequestsNormalizer.php`, `RealSubscribersToKlaviyoRequestNormalizer.php`, `SubscribeToListRequestNormalizer.php` |
| DI | `src/Resources/config/services/gateway_services.xml` |

## Acceptance criteria

- [ ] Newsletter signup / cookie-identified flows send **`properties.language`** on Klaviyo profile payloads consistent with storefront locale (same mechanism as customers).
- [ ] Subscriber sync jobs (historical + realtime) send language on list subscription payloads **when** the Klaviyo API accepts `properties` on nested profiles in that endpoint.
- [ ] Unsubscribe / opt-out list behavior unchanged.

## Verification

1. **PHP syntax (local):** e.g. `find src -name '*.php' -print0 | xargs -0 -n1 php -l` from the plugin root (requires PHP).

2. **Klaviyo:** In staging, confirm **profile-subscription-bulk-create-job** (and any realtime subscribe endpoint you use) accepts `attributes.properties.language`. If an endpoint rejects nested `properties`, document it and consider a follow-up profile-update call—only after confirming API behavior.

3. **Manual QA:** Newsletter subscribe on a non-default language storefront → confirm profile **language** in Klaviyo; run subscriber sync job → confirm language on bulk-imported profiles.

## Draft PR template (paste into GitHub)

```markdown
## Purpose
Spec / implementation handoff: newsletter recipients sync **language** to Klaviyo like registered customers.

## Links
- Spec: `docs/plans/newsletter-language-sync.md`

## Acceptance criteria
See checklist in the doc above.

## Notes for reviewer
- `@mention` — verify Klaviyo bulk subscribe accepts `properties.language` in your environment.
```

## Signature changes (for forks / downstream)

If anything extends this plugin:

- `NewsletterSubscriberPropertiesTranslator::translateSubscriber(Context $context, NewsletterRecipientEntity $subscriber)`
- `KlaviyoGateway::subscribeToKlaviyoList(SalesChannelEntity $channel, Context $context, NewsletterRecipientCollection $recipients, string $listId)`
- `SubscribersToKlaviyoRequestsTranslator::translateToAddProfilesRequest(Context $context, …)`
- `RealSubscribersToKlaviyoRequestsTranslator::translateToSubscribeRequest(Context $context, …)`
