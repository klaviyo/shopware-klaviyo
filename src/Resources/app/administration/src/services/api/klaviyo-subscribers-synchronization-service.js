const {ApiService} = Shopware.Classes;

class KlaviyoSubscribersSynchronizationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoSubscribersSynchronizationApiService';
    }

    scheduleSynchronization() {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .post('_action/klaviyo/subscribers/synchronization/schedule', {}, {headers});
    }
}

export default KlaviyoSubscribersSynchronizationApiService;
