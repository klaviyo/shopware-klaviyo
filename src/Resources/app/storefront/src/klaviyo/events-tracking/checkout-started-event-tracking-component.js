import Plugin from 'src/plugin-system/plugin.class';
import KlaviyoGateway from "../util/gateway";

export default class KlaviyoCheckoutStartedEventTrackingComponent extends Plugin {
    static options = {
        startedCheckoutEventTrackingRequest: null
    }

    init() {
        if (!this.options.startedCheckoutEventTrackingRequest && console) {
            console.error('Checkout Started Event Tracking DTO was not set');
            return;
        }

        KlaviyoGateway.push(["track", "Started Checkout", this.options.startedCheckoutEventTrackingRequest]);
    }
}
