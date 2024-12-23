import template from './klaviyo-integration-settings-synchronization-control.html.twig';
import JobInteractor from './job-interactor';

const {Component, Mixin} = Shopware;
const {date} = Shopware.Utils.format;

Component.register('klaviyo-integration-settings-synchronization-control', {
    template,

    inject: [
        'klaviyoHistoricalEventsSynchronizationApiService',
        'klaviyoSubscribersSynchronizationApiService',
    ],

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
            lastCalledFunction: null
        }
    },

    computed: {
        isSwitchingFunctions() {
            return (funcName) => this.lastCalledFunction && this.lastCalledFunction !== funcName;
        }
    },

    methods: {
        scheduleHistoricalEventsSynchronization() {
            if (this.isSwitchingFunctions('scheduleHistoricalEventsSynchronization')) {
                this.showConfirmation(
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmActionTitle'),
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.label', 0, {
                        entityName: this.$tc('klaviyo_integration_plugin.subscribers.schedule_synchronization.button_label'),
                        docUrl: '(<a href="https://developers.klaviyo.com/en/docs/rate_limits_and_error_handling#rate-limits)" target="_blank">see Rate limits, status codes, and errors</a>)'
                    }),
                    () => {
                        this.lastCalledFunction = null;
                        this.performHistoricalEventsSynchronization();
                    }
                );
            } else {
                this.performHistoricalEventsSynchronization();
            }
        },

        scheduleSubscribersSynchronization() {
            if (this.isSwitchingFunctions('scheduleSubscribersSynchronization')) {
                this.showConfirmation(
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmActionTitle'),
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.label', 0, {
                        entityName: this.$tc('klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.button_label'),
                        docUrl: '(<a href="https://developers.klaviyo.com/en/docs/rate_limits_and_error_handling#rate-limits)" target="_blank">see Rate limits, status codes, and errors</a>)'
                    }),
                    () => {
                        this.lastCalledFunction = null;
                        this.performSubscribersSynchronization();
                    }
                );
            } else {
                this.performSubscribersSynchronization();
            }
        },
        performHistoricalEventsSynchronization() {
            const promise = this.historicalEventsJobInteractor.scheduleSynchronization();
            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.lastCalledFunction = 'scheduleHistoricalEventsSynchronization';

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
        performSubscribersSynchronization() {
            const promise = this.subscribersSynchronizationJobInteractor.scheduleSynchronization();

            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.lastCalledFunction = 'scheduleSubscribersSynchronization';

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
        showConfirmation(title, message, onConfirm) {
            this.$store.dispatch('notification/createNotification', {
                title: title,
                message: message,
                variant: 'warning',
                autoClose: false,
                system: true,
                actions: [
                    {
                        label: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmLabel'),
                        method: onConfirm
                    },
                    {
                        label: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.cancelLabel'),
                        method: () => {
                            this.createNotificationInfo({
                                message: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.actionCanceled')
                            });
                        }
                    }
                ]
            });
        }
    }
});
