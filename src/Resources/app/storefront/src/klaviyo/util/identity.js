import HttpClient from 'src/service/http-client.service';
import KlaviyoCookie from './cookie';

/**
 * Loads the visitor's identity from the no-store endpoint, only when the marker cookie is set.
 * Cached in sessionStorage per marker value; a new marker invalidates the entry.
 */
export default class KlaviyoIdentity {
    static STORAGE_KEY = 'klaviyo_identity';
    static MARKER_COOKIE = 'klaviyo_identified';
    static TTL_MS = 5 * 60 * 1000;
    static LOGOUT_LINK_SELECTOR = 'a[href*="/account/logout"]';

    /**
     * @param {string} url
     * @returns {Promise<object|null>}
     */
    static load(url) {
        if (!url) {
            return Promise.resolve(null);
        }

        this._registerLogoutListener();

        const marker = this._marker();
        if (!marker) {
            this.clear();

            return Promise.resolve(null);
        }

        const cached = this._readCache(url, marker);
        if (cached !== undefined) {
            return Promise.resolve(cached);
        }

        // share one in-flight request between consumers on the same page
        const key = `${url}|${marker}`;
        if (window._klaviyoIdentityRequest && window._klaviyoIdentityRequest.key === key) {
            return window._klaviyoIdentityRequest.promise;
        }

        const promise = new Promise((resolve) => {
            new HttpClient().get(url, (responseText, request) => {
                let identity = null;

                if (window._klaviyoIdentityRequest && window._klaviyoIdentityRequest.key === key) {
                    window._klaviyoIdentityRequest = undefined;
                }

                // marker changed while loading (account switch in another tab): load again for the new one
                if (this._marker() !== marker) {
                    resolve(this.load(url));

                    return;
                }

                // only cache a parsed 2xx answer; failures are retried on the next page view
                if (request && request.status >= 200 && request.status < 300) {
                    try {
                        const response = JSON.parse(responseText);
                        identity = response && response.identity ? response.identity : null;
                        this._writeCache(url, marker, identity);
                    } catch (e) {
                        identity = null;
                    }
                }

                resolve(identity);
            });
        });
        window._klaviyoIdentityRequest = { key, promise };

        return promise;
    }

    // true while the current marker has not been pushed as identify yet (events wait, see gateway.js)
    static isIdentifyPending() {
        const marker = this._marker();

        return !!marker && window._klaviyoIdentifiedMarker !== marker;
    }

    static identifyDone() {
        window._klaviyoIdentifiedMarker = this._marker();
    }

    static _marker() {
        return KlaviyoCookie.getCookie(this.MARKER_COOKIE) || null;
    }

    static clear() {
        window._klaviyoIdentityRequest = undefined;

        try {
            window.sessionStorage.removeItem(this.STORAGE_KEY);
        } catch (e) {
            // storage not available
        }
    }

    // drop the cache on logout click, before the redirect
    static _registerLogoutListener() {
        if (window._klaviyoIdentityLogoutListener) {
            return;
        }
        window._klaviyoIdentityLogoutListener = true;

        document.addEventListener('click', (event) => {
            if (event.target && event.target.closest && event.target.closest(this.LOGOUT_LINK_SELECTOR)) {
                this.clear();
            }
        }, true);
    }

    static _readCache(url, marker) {
        try {
            const raw = window.sessionStorage.getItem(this.STORAGE_KEY);
            if (!raw) {
                return undefined;
            }

            const entry = JSON.parse(raw);
            if (!entry || entry.url !== url || entry.marker !== marker || (Date.now() - entry.ts) > this.TTL_MS) {
                return undefined;
            }

            return entry.identity;
        } catch (e) {
            return undefined;
        }
    }

    static _writeCache(url, marker, identity) {
        try {
            window.sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({ url, marker, identity, ts: Date.now() }));
        } catch (e) {
            // storage not available
        }
    }
}
