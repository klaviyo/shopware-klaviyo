export default class KlaviyoGateway {
    static push() {
        if (this.isKlaviyoPersonIdentified()) {
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

        window._odKlaviyoBufferWatcher = setInterval(() => {
            if (this.isKlaviyoPersonIdentified()) {
                window._learnq.push(...window._odKlaviyoBuffer);
                window._odKlaviyoBuffer = [];
                clearInterval(window._odKlaviyoBufferWatcher);
            }
        }, 500);
    }

    static isKlaviyoPersonIdentified() {
        return typeof window._learnq.isIdentified === 'function' && window._learnq.isIdentified() === true;
    }
}
