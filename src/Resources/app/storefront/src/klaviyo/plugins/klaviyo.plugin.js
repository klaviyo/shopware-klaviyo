import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';
import KlaviyoCookie from '../util/cookie'

/**
 * This component is responsible for Klaviyo script initialization on storefront.
 * We have some component behavior-defining restrictions from cookie consent and Klaviyo script deferred initialization.
 * There is all possible Klaviyo script initialization cases:
 *
 * Glossary:
 * - "INTERACT" -> Customer interacts with page by scrolling it.
 * - "CONSENT" -> Customer allowed Klaviyo cookies.
 *
 * 1. Preconditions: deferred script initialization is ON
 *    Steps:
 *    A) INTERACT -> nothing happens;
 *    B) CONSENT -> "interacted_with_page" is added to localStorage,
 *                  "od-klaviyo-track-allow" is added to cookies,
 *                  Klaviyo script is initialized.
 *
 * 2. Preconditions: deferred script initialization is ON
 *    Steps:
 *    A) CONSENT -> "interacted_with_page" is added to localStorage,
 *                  "od-klaviyo-track-allow" is added to cookies,
 *                  Klaviyo script is initialized;
 *    B) INTERACT -> nothing happens.
 *
 * 3. Preconditions: deferred script initialization is OFF
 *    Steps:
 *    A) INTERACT -> nothing happens;
 *    B) CONSENT -> "od-klaviyo-track-allow" is added + Klaviyo script is initialized.
 *
 * 4. Preconditions: deferred script initialization is OFF
 *    Steps:
 *    A) CONSENT -> "od-klaviyo-track-allow" is added + Klaviyo script is initialized;
 *    B) INTERACT -> "nothing happens.
 *
 * Note: If deferred script initialization is enabled, customer had interacted with page and reloaded current page
 * or opened next page, Klaviyo script will be initialized immediately
 */
export default class KlaviyoTracking extends Plugin {
    static options = {
        klaviyoInitializedStorageKey: 'interacted_with_page',
        scriptInitialized: false,
        afterInteraction: false,
        publicApiKey: '',
        cookieConsent: ''
    };

    init() {
        this.storage = Storage;

        if (this.canInitializeKlaviyoScript()) {
            this.initKlaviyoScript();
        }

        this.registerEvents();
    }

    registerEvents() {
        if (this.isPageInteractionRequired()) {
            window.addEventListener('scroll', function () {
                this.storage.setItem(this.options.klaviyoInitializedStorageKey, 'true');
                if (this.canInitializeKlaviyoScript()) {
                    this.initKlaviyoScript();
                }
            }.bind(this), {once: true});
        }
    }

    cookiebotOnDecline() {
        const scriptList = document.querySelectorAll("script[type='text/javascript']");
        for (let i = 0; i < scriptList.length; i++) {
            if (typeof scriptList[i].src === 'string' && scriptList[i].src.includes('klaviyo.com')) {
                scriptList[i].parentNode.removeChild(scriptList[i]);
            }
        }
        KlaviyoCookie.setCookie('__kla_id', null, -1);
    }

    onKlaviyoCookieConsentAllowed() {
        // As far as cookie accept event can be recognized as "page interaction",
        // we are set our interaction key to the storage.
        if (this.options.afterInteraction) {
            this.storage.setItem(this.options.klaviyoInitializedStorageKey, 'true')
        }

        if (this.canInitializeKlaviyoScript()) {
            this.initKlaviyoScript();
        }
    }

    onKlaviyoCookieConsentManagerAllowed() {
        if (KlaviyoCookie.getCookie('od-klaviyo-track-allow') === null) {
            KlaviyoCookie.setCookie('od-klaviyo-track-allow', 1, 30);
        }

        this.onKlaviyoCookieConsentAllowed();
    }

    isAllowToTrack() {
        switch (this.options.cookieConsent) {
            case 'nothing':
                // In this config, always loading klaviyo cookies
                return true;
            case 'shopware':
            case 'consentmanager':
                // In this config, shopware default cookies is checked
                return KlaviyoCookie.getCookie('od-klaviyo-track-allow');
            case 'cookiebot':
                // In this config, cookiebot cookies is checked
                return Cookiebot.consent.marketing && Cookiebot.consent && Cookiebot.consent.marketing;
            default:
                return false;
        }
    }

    isPageInteractionRequired() {
        return this.isAllowToTrack()
            && this.options.afterInteraction
            && this.storage.getItem(this.options.klaviyoInitializedStorageKey) === null;
    }

    canInitializeKlaviyoScript() {
        return !this.options.scriptInitialized
            && this.isAllowToTrack()
            && !this.isPageInteractionRequired();
    }

    initKlaviyoScript() {
        const initializer = function () {
            let script = document.createElement('script');
            script.type = 'text/javascript';
            script.setAttribute('async', true);
            script.src = 'https://static.klaviyo.com/onsite/js/klaviyo.js?company_id=' + this.options.publicApiKey;

            document.body.appendChild(script);
            this.options.scriptInitialized = true;
        }.bind(this)

        initializer();
    }
}
