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

//window.addEventListener("UC_UI_CMP_EVENT_TYPE.ACCEPT_ALL", setCookieConsentAllowed);
//window.addEventListener("UC_UI_CMP_EVENT_TYPE.DENY_ALL", setCookieOnDecline);

if (window.cmp_id) {
    __cmp("addEventListener", ["consentrejected", setCookieOnDecline, false], null);
    __cmp("addEventListener", ["consentapproved", setCookieConsentManagerAllowed, false], null);
}


function handleConsentChange(consents) {
    console.log('Usercentrics consent event:', consents);

    // Check the consent status
    const allServices = consents.consents;
    const allAllowed = allServices.every(service => service.status === true);
    const allDenied = allServices.every(service => service.status === false);

    if (allAllowed) {
        console.log('All services were allowed.');
        setCookieConsentAllowed()
    } else if (allDenied) {
        console.log('All services were denied.');
        setCookieOnDecline()
    } else {
        console.log('Mixed consent status.');
        setCookieConsentAllowed()
    }
}

// Wait for the Usercentrics SDK to be ready
window.addEventListener('ucReady', function() {
    console.log('Usercentrics is ready');

    // Add event listener for consent changes
    Usercentrics.addEventListener('consentChanged', function(event) {
        handleConsentChange(event.detail);
    });

    // Log the initial state
    Usercentrics.getConsents().then(handleConsentChange);
});