import KlaviyoIdentity from './identity';

export default class KlaviyoGateway {
    // events wait for the identify of the current marker, but never longer than this
    static IDENTIFY_WAIT_MS = 10000;

    static push() {
        if (this.canPush()) {
            window._learnq.push(...arguments);
        } else {
            window._odKlaviyoBuffer = window._odKlaviyoBuffer || [];
            window._odKlaviyoBuffer.push(...arguments);
            this.ensureBufferWatcher();
        }
    }

    static ensureBufferWatcher() {
        if (window._odKlaviyoBufferWatcher !== undefined) {
            return;
        }

        const deadline = Date.now() + this.IDENTIFY_WAIT_MS;
        window._odKlaviyoBufferWatcher = setInterval(() => {
            if (this.canPush(Date.now() > deadline)) {
                window._learnq.push(...window._odKlaviyoBuffer);
                window._odKlaviyoBuffer = [];
                clearInterval(window._odKlaviyoBufferWatcher);
                // allow a new watcher when events buffer again (marker changed, deadline flush)
                window._odKlaviyoBufferWatcher = undefined;
            }
        }, 500);
    }

    static canPush(ignorePendingIdentify = false) {
        return this.isKlaviyoPersonIdentified() && (ignorePendingIdentify || !KlaviyoIdentity.isIdentifyPending());
    }

    static isKlaviyoPersonIdentified() {
        return typeof window._learnq.isIdentified === 'function' && window._learnq.isIdentified() === true;
    }
}
