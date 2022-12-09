export default class CookieBotConsentService  {

    constructor(initCallback) {
        this.initCallback = initCallback;
    }

    bootstrap() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, (updateCookies) => {
            if (updateCookies.detail(['od-klaviyo-track-allow'])) {
                this.initCallback();
            }
        })
    }
}