import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';
import KlaviyoCookie from '../util/cookie'

export default class KlaviyoTracking extends Plugin {
    static options = {
        klaviyoInitializedStorageKey: 'klaviyoInitializedStorageKey',
        cookieOff: '__kla_off'
    };

    init() {
        this.refreshCookies();
        this.storage = Storage;
        if (this.options.afterInteraction) {
            if (this.storage.getItem(this.options.klaviyoInitializedStorageKey) !== null) {
                return this._initKlaviyo();
            } else {
                return this.registerEvents();
            }
        }

        this._initKlaviyo();
    }

    refreshCookies() {
        if (!this.options.customerId && !KlaviyoCookie.getCookie('od-klaviyo-track-allow')) {
            KlaviyoCookie.setCookie(this.options.cookieOff, true, 30)
        } else {
            KlaviyoCookie.setCookie(this.options.cookieOff, true, -1)
        }
    }

    registerEvents() {
        window.addEventListener('scroll', this._prepareForInitialization.bind(this), {once: true});
    }

    _prepareForInitialization() {
        this.storage.setItem(this.options.klaviyoInitializedStorageKey, '')
        this._initKlaviyo();
    }

    _initKlaviyo() {
         let script = document.createElement('script');
         script.type = 'text/javascript';
         script.setAttribute('async', true);
         script.src = 'https://static.klaviyo.com/onsite/js/klaviyo.js?company_id=' + this.options.publicApiKey;

         document.body.appendChild(script);
    }
}
