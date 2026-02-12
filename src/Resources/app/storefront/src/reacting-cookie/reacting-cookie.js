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

const SERVICE_NAME = 'klaviyo';
const ALL_ACCEPTED = 'ALL_ACCEPTED';
window.addEventListener('UC_CONSENT', (event) => {
    const consent = event.detail?.consent || {};
    const services = consent.services || {};
    const klaviyoService = Object.values(services).find(function (service) {
        return service && service.name && service.name.toLowerCase() === SERVICE_NAME;
    });

    if (klaviyoService) {
        if (klaviyoService.consent && klaviyoService.consent.given) {
            setCookieConsentAllowed();
        } else {
            setCookieOnDecline();
        }
        return;
    }

    const isAccepted = (consent.status === ALL_ACCEPTED);
    if (isAccepted) {
        setCookieConsentAllowed();
    } else {
        setCookieOnDecline();
    }
});

if (window.cmp_id) {
    __cmp("addEventListener", ["consentrejected", setCookieOnDecline, false], null);
    __cmp("addEventListener", ["consentapproved", setCookieConsentManagerAllowed, false], null);
}
