import enGB from './snippet/en-GB.json';
import template from './template.html.twig';
import './styles.scss';
import JobInteractor from './job-interactor';

const {Component, Mixin} = Shopware;
const {date} = Shopware.Utils.format;

Component.register('klaviyo-historical-events-synchronization-control', {
    template,

    inject: [
        'klaviyoHistoricalEventsSynchronizationApiService',
        'klaviyoSubscribersSynchronizationApiService',
    ],

    snippets: {
        'en-GB': enGB,
    },

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        const historicalEventsJobInteractor = new JobInteractor(
            this.klaviyoHistoricalEventsSynchronizationApiService,
            date
        );
        const subscribersSynchronizationJobInteractor = new JobInteractor(
            this.klaviyoSubscribersSynchronizationApiService,
            date
        );

        return {
            historicalEventsJobInteractor: historicalEventsJobInteractor,
            subscribersSynchronizationJobInteractor: subscribersSynchronizationJobInteractor,
        }
    },

    methods: {
        scheduleHistoricalEventsSynchronization() {
            const promise = this.historicalEventsJobInteractor.scheduleSynchronization();
            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.success'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_RUNNING') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.is_running'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_SCHEDULED') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.is_scheduled'
                        )
                    });
                } else {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.failed'
                        )
                    });
                }
            }.bind(this)).catch(function (error) {
                this.createNotificationError({
                    message: this.$tc(
                        'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.failed'
                    )
                });
            }.bind(this));
        },
        scheduleSubscribersSynchronization() {
            const promise = this.subscribersSynchronizationJobInteractor.scheduleSynchronization();

            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.success'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_RUNNING') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.is_running'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_SCHEDULED') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.is_scheduled'
                        )
                    });
                } else {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.failed'
                        )
                    });
                }
            }.bind(this)).catch(function () {
                this.createNotificationError({
                    message: this.$tc(
                        'klaviyo_integration_plugin.subscribers.schedule_synchronization.failed'
                    )
                });
            }.bind(this));
        },
        resetSubscribersSynchronizationState() {
            this.subscribersSynchronizationJobInteractor.resetSynchronizationState();
        },
        resetHistoricalEventsSynchronizationState() {
            this.historicalEventsJobInteractor.resetSynchronizationState();
        },
    }
});
