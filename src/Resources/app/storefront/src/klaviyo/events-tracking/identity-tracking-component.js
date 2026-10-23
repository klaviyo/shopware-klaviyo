import Plugin from 'src/plugin-system/plugin.class';
import KlaviyoIdentity from '../util/identity';

export default class KlaviyoIdentityTrackingComponent extends Plugin {
    static options = {
        identityUrl: null
    }

    init() {
        window._learnq = window._learnq || [];

        KlaviyoIdentity.load(this.options.identityUrl)
            .catch(() => null)
            .then((identity) => {
                try {
                    if (identity) {
                        /**
                         * We are using "_learnq" instead of safe approach with "KlaviyoGateway" component
                         * because 'identify' request must be very first in event queue and processed by Klaviyo as it is.
                         * Other events like "Track Vieved Product, etc." must be deferred until Klaviyo JS lib will identify us.
                         */
                        window._learnq.push(
                            [
                                'identify',
                                {
                                    '$email': identity.email,
                                    '$first_name': identity.firstName,
                                    '$last_name': identity.lastName,
                                    '$phone_number': identity.phoneNumber,
                                    '$city': identity.city,
                                    '$region': identity.region,
                                    '$country': identity.country,
                                    '$zip': identity.zip,
                                    'birthday': identity.birthday,
                                    'language': identity.language,
                                    'salesChannelId': identity.salesChannelId,
                                    'salesChannelName': identity.salesChannelName,
                                    'customerGroup': identity.customerGroup
                                }
                            ]
                        );
                    }
                } finally {
                    // always release the event buffer, see gateway.js
                    KlaviyoIdentity.identifyDone();
                }
            });
    }
}
