const {ApiService} = Shopware.Classes;

class KlaviyoApiKeyValidatorService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoApiKeyValidatorService';
    }

    validate(privateKey, publicKey, listId) {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .post('/_action/od-api-key-validate', {
                "privateKey": privateKey, "publicKey": publicKey, "listId": listId
            }, {headers});
    }

    getList(privateKey, publicKey) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post('/_action/od-get-subscriber-lists', {
                "privateKey": privateKey, "publicKey": publicKey
            }, {headers});
    }

    validateListById(privateKey, publicKey, listId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post('/_action/od-list-id-validate', {
                "privateKey": privateKey, "publicKey": publicKey, "listId": listId
            }, {headers});
    }
}

export default KlaviyoApiKeyValidatorService;
