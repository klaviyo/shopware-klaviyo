import template from './klaviyo-job-listing.html.twig';
import './klaviyo-job-listing.scss';

const {Component} = Shopware;

Component.register('klaviyo-job-listing', {
    template,

    computed: {
        klaviyoJobTypes() {
            return [
                'od-klaviyo-events-sync-handler',
                'od-klaviyo-cart-event-sync-handler',
                'od-klaviyo-full-order-sync-handler',
                'od-klaviyo-full-subscriber-sync-handler',
                'od-klaviyo-order-event-sync-handler',
                'od-klaviyo-order-sync-handler',
                'od-klaviyo-subscriber-sync-handler'
            ];
        },
    },

    methods: {
        onRefresh() {
            this.$refs.jobListing.onRefresh();
        }
    }
});
