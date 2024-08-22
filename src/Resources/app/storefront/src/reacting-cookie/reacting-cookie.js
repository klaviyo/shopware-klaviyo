import {COOKIE_CONFIGURATION_UPDATE} from 'src/plugin/cookie/cookie-configuration.plugin';
import Iterator from 'src/helper/iterator.helper';

document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, eventCallback);

function setCookieConsentAllowed() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.onKlaviyoCookieConsentAllowed();
        console.log('setCookieConsentAllowed');
    })
}

function setCookieConsentManagerAllowed() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.onKlaviyoCookieConsentManagerAllowed();
        console.log('setCookieConsentManagerAllowed');
    })
}

function setCookieOnDecline() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.cookiebotOnDecline();
        console.log('setCookieOnDecline');
    })
}

function eventCallback(updatedCookies) {
    if (updatedCookies && updatedCookies.detail['od-klaviyo-track-allow']) {
        setCookieConsentAllowed();
    }
}

window.addEventListener('CookiebotOnAccept', setCookieConsentAllowed);
window.addEventListener('CookiebotOnDecline', setCookieOnDecline);

window.addEventListener('UC_UI_CMP_EVENT', function(event) {

    if (event.detail) {
        switch (event.detail.type) {
            case 'ACCEPT_ALL':
                setCookieConsentAllowed();
                break;
            case 'DENY_ALL':
                setCookieOnDecline();
                break;
            default:
                setCookieConsentAllowed();
        }
    }
});

if (window.cmp_id) {
    __cmp("addEventListener", ["consentrejected", setCookieOnDecline, false], null);
    __cmp("addEventListener", ["consentapproved", setCookieConsentManagerAllowed, false], null);
}
