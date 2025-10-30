import Plugin from 'src/plugin-system/plugin.class';

export default class KlaviyoIdentityTrackingComponent extends Plugin {
    static options = {
        customerIdentityInfo: null
    }

    init() {
        window._learnq = window._learnq || [];
        if (this.options.customerIdentityInfo) {
            /**
             * We are using "_learnq" instead of safe approach with "KlaviyoGateway" component
             * because 'identify' request must be very first in event queue and processed by Klaviyo as it is.
             * Other events like "Track Vieved Product, etc." must be deferred until Klaviyo JS lib will identify us.
             */
            window._learnq.push(
                [
                    'identify',
                    {
                        '$email': this.options.customerIdentityInfo.email,
                        '$first_name': this.options.customerIdentityInfo.firstName,
                        '$last_name': this.options.customerIdentityInfo.lastName,
                        '$phone_number': this.options.customerIdentityInfo.phoneNumber,
                        '$city': this.options.customerIdentityInfo.city,
                        '$region': this.options.customerIdentityInfo.region,
                        '$country': this.options.customerIdentityInfo.country,
                        '$zip': this.options.customerIdentityInfo.zip,
                        'birthday': this.options.customerIdentityInfo.birthday,
                        'language': this.options.customerIdentityInfo.language,
                        'salesChannelId': this.options.customerIdentityInfo.salesChannelId,
                        'salesChannelName': this.options.customerIdentityInfo.salesChannelName,
                        'customerGroup': this.options.customerIdentityInfo.customerGroup
                    }
                ]
            );
        }
    }
}
