import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';

export default class KlaviyoTracking extends Plugin {
    static options = {
        klaviyoInitializedStorageKey: ''
    };

    init() {
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