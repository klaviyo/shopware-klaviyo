class KlaviyoHistoricalEventsSynchronizationApiService {
    constructor(httpClient, loginService) {
        this.httpClient = httpClient;
        this.loginService = loginService;
        this.name = 'klaviyoHistoricalEventsSynchronizationApiService';
    }

    getJobStatus() {
        const headers = this.getHeaders();
        return this.httpClient.get('/klaviyo/integration/job/_action/historical-event-tracking/synchronization/get_status', {}, { headers });
    }

    scheduleSynchronization() {
        const headers = this.getHeaders();
        return this.httpClient.post('/klaviyo/integration/job/_action/historical-event-tracking/synchronization/schedule', {}, { headers });
    }

    getHeaders() {
        return {
            Accept: 'application/json',
            Authorization: `Bearer ${this.loginService.getToken()}`,
            'Content-Type': 'application/json'
        };
    }
}

export default KlaviyoHistoricalEventsSynchronizationApiService;
