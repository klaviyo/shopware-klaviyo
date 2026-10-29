const { ApiService } = Shopware.Classes;

/**
 * Downloads the Klaviyo promotion-code CSV through the authenticated admin HTTP client.
 */
class KlaviyoPromotionExportApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoPromotionExportApiService';
    }

    /**
     * Fetches the CSV (optionally limited to one promotion) and hands it to the browser
     * as a file download. The returned promise rejects on HTTP errors (401/403/500).
     *
     * @param {string|null} promotionId
     * @returns {Promise<void>}
     */
    downloadCsv(promotionId = null) {
        const headers = this.getBasicHeaders({ Accept: 'text/csv' });
        const params = promotionId ? { id: promotionId } : {};

        return this.httpClient
            .get('klaviyo/integration/promotion/export', { headers, params, responseType: 'blob' })
            .then((response) => {
                this.triggerDownload(response.data, this.getFileNameFromResponse(response) || 'promotions.csv');
            });
    }

    /**
     * @param {Blob} blob
     * @param {string} fileName
     */
    triggerDownload(blob, fileName) {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        link.remove();

        // Revoke after the click has been processed by the browser's download manager.
        window.setTimeout(() => window.URL.revokeObjectURL(url), 1000);
    }

    /**
     * @param {Object} response axios response
     * @returns {string|null}
     */
    getFileNameFromResponse(response) {
        const disposition = response?.headers?.['content-disposition'] ?? '';
        const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition);

        return match ? decodeURIComponent(match[1]) : null;
    }
}

export default KlaviyoPromotionExportApiService;
