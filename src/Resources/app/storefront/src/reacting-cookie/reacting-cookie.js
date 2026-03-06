import {COOKIE_CONFIGURATION_UPDATE} from 'src/plugin/cookie/cookie-configuration.plugin';
import Iterator from 'src/helper/iterator.helper';

document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, eventCallback);

function setCookieConsentAllowed() {
    Iterator.iterate(PluginManager.getPluginInstances('KlaviyoTracking'), (plugin) => {
        plugin.onKlaviyoCookieConsentAllowed();
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


const SERVICE_NAME = 'klaviyo';
const ALL_ACCEPTED = 'ALL_ACCEPTED';
window.addEventListener('UC_CONSENT', (event) => {
    const consent = event.detail?.consent || {};
    const services = consent.services || {};
    const klaviyoService = Object.values(services).find(service => service?.name?.toLowerCase() === SERVICE_NAME);

    if (klaviyoService) {
        klaviyoService.consent?.given ? setCookieConsentAllowed() : setCookieOnDecline();
        return;
    }

    const isAccepted = consent.status === ALL_ACCEPTED;
    isAccepted ? setCookieConsentAllowed() : setCookieOnDecline();
});

if (window.cmp_id) {
    __cmp("addEventListener", ["consentrejected", setCookieOnDecline, false], null);
    __cmp("addEventListener", ["consentapproved", setCookieConsentAllowed, false], null);
}
