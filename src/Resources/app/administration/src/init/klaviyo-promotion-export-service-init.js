import KlaviyoPromotionExportApiService from '../services/api/klaviyo-promotion-export-service';

Shopware.Service().register('klaviyoPromotionExportApiService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new KlaviyoPromotionExportApiService(initContainer.httpClient, container.loginService);
});
