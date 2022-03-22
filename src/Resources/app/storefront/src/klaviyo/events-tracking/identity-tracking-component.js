import Plugin from 'src/plugin-system/plugin.class';

export default class KlaviyoIdentityTrackingComponent extends Plugin {
    static options = {
        customerIdentityInfo: null
    }

    init() {
        window._learnq = window._learnq || [];
        if (this.options.customerIdentityInfo) {
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
                        'Birthday': this.options.customerIdentityInfo.birthday
                    }
                ]
            );
        }
    }
}