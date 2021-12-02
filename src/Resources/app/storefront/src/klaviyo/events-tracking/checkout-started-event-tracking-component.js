import Plugin from 'src/plugin-system/plugin.class';

export default class KlaviyoCheckoutStartedEventTrackingComponent extends Plugin {
    static options = {
        startedCheckoutEventTrackingRequest: null
    }

    init() {
        console.log('working');
        window._learnq = window._learnq || [];

        if (!this.options.startedCheckoutEventTrackingRequest && console) {
            console.error('Checkout Started Event Tracking DTO was not set');
            return;
        }

        window._learnq.push(["track", "Started Checkout", this.options.startedCheckoutEventTrackingRequest]);
    }
}