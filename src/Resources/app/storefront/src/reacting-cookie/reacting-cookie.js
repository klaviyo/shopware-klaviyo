import {COOKIE_CONFIGURATION_UPDATE} from 'src/plugin/cookie/cookie-configuration.plugin';
import Iterator from 'src/helper/iterator.helper';

document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, eventCallback);

function setCookieConsentAllowed() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.onKlaviyoCookieConsentAllowed();
    })
}

function setCookieConsentManagerAllowed() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.onKlaviyoCookieConsentManagerAllowed();
    })
}

function setCookieOnDecline() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.cookiebotOnDecline();
    })
}

function eventCallback(updatedCookies) {
    if (updatedCookies && updatedCookies.detail['od-klaviyo-track-allow']) {
        setCookieConsentAllowed();
    }
}

window.addEventListener('CookiebotOnAccept', setCookieConsentAllowed);
window.addEventListener('CookiebotOnDecline', setCookieOnDecline);

if (window.cmp_id) {
    __cmp("addEventListener", ["consentrejected", setCookieOnDecline, false], null);
    __cmp("addEventListener", ["consentapproved", setCookieConsentManagerAllowed, false], null);
}
