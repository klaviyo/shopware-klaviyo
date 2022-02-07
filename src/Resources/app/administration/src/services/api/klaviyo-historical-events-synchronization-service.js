const {ApiService} = Shopware.Classes;

class KlaviyoHistoricalEventsSynchronizationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoHistoricalEventsSynchronizationApiService';
    }

    scheduleSynchronization() {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .post('_action/klaviyo/historical-event-tracking/synchronization/schedule', {}, {headers});
    }
}

export default KlaviyoHistoricalEventsSynchronizationApiService;
