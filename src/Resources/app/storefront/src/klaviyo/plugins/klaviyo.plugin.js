import Plugin from 'src/plugin-system/plugin.class';
import Storage from 'src/helper/storage/storage.helper';

export default class KlaviyoTracking extends Plugin {
    static options = {
        klaviyoAfterFirstInteraction: 'afterFirstInteraction'
    };

    init() {
        this.storage = Storage;
        if (this.storage.getItem(this.options.klaviyoAfterFirstInteraction) !== null) {
            return this._initKlaviyo();
        }
        this.registerEvents();
    }

    registerEvents() {
        window.addEventListener('scroll', this._prepareForInitialization.bind(this), {once: true});
    }

    _prepareForInitialization() {
        this.storage.setItem(this.options.klaviyoAfterFirstInteraction, '')
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