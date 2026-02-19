import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';
import KlaviyoCookie from '../util/cookie'

export default class KlaviyoTracking extends Plugin {
    static STORAGE_VALUE = '1';
    static options = {
        storageKey: 'interacted_with_page',
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

        if (!this.isPageInteractionRequired()) {
            return;
        }

        window.addEventListener('scroll', function () {
            this.markAsInteracted();
            if (!this.options.scriptInitialized && this.isAllowToTrack()) {
                this.initKlaviyoScript();
            }
        }.bind(this), {once: true});
    }

    cookiebotOnDecline() {
        this.unsetKlaviyoCookie();
        this.options.scriptInitialized = false;
        this.storage.removeItem(this.options.storageKey);
        const scriptList = document.querySelectorAll("script[type='text/javascript']");
        for (let i = 0; i < scriptList.length; i++) {
            if (typeof scriptList[i].src === 'string' && scriptList[i].src.includes('klaviyo.com')) {
                scriptList[i].parentNode.removeChild(scriptList[i]);
            }
        }
        KlaviyoCookie.setCookie('__kla_id', null, -1);
    }

    onKlaviyoCookieConsentAllowed() {
        this.setKlaviyoCookie();

        if (this.options.afterInteraction) {
            this.markAsInteracted()
        }

        if (this.canInitializeKlaviyoScript()) {
            this.initKlaviyoScript();
        }
    }

    isAllowToTrack() {
        switch (this.options.cookieConsent) {
            case 'nothing':
                return true;
            case 'shopware':
            case 'consentmanager':
            case 'usercentrics':
            case 'cookiebot':
                return KlaviyoCookie.getCookie('od-klaviyo-track-allow');
            default:
                return false;
        }
    }

    isPageInteractionRequired() {
        // Check if the option `Initialize Klaviyo After First Interaction With Page.` is enabled.
        if (!this.options.afterInteraction) {
            return false;
        }

        // Check if the script has already been initialized.
        if (this.options.scriptInitialized) {
            return false;
        }

        // Check if page interaction already happened.
        return !this.hasPageInteraction();
    }

    canInitializeKlaviyoScript() {
        if (this.isPageInteractionRequired()) {
            return this.hasPageInteraction();
        }

        return !this.options.scriptInitialized && this.isAllowToTrack();
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

    setKlaviyoCookie() {
        KlaviyoCookie.setCookie('od-klaviyo-track-allow', 'true', 30);
    }

    unsetKlaviyoCookie() {
        KlaviyoCookie.setCookie('od-klaviyo-track-allow', '', -1);
    }

    markAsInteracted() {
        this.storage.setItem(this.options.storageKey, KlaviyoTracking.STORAGE_VALUE);
    }

    hasPageInteraction() {
        return this.storage.getItem(this.options.storageKey) === KlaviyoTracking.STORAGE_VALUE;
    }
}
