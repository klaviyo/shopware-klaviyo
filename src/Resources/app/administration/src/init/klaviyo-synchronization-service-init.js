import KlaviyoHistoricalEventsSynchronizationApiService from '../services/api/klaviyo-historical-events-synchronization-service'
import KlaviyoSubscribersSynchronizationApiService from "../services/api/klaviyo-subscribers-synchronization-service";

Shopware.Service().register('klaviyoHistoricalEventsSynchronizationApiService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new KlaviyoHistoricalEventsSynchronizationApiService(initContainer.httpClient, container.loginService);
});
Shopware.Service().register('klaviyoSubscribersSynchronizationApiService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new KlaviyoSubscribersSynchronizationApiService(initContainer.httpClient, container.loginService);
});
