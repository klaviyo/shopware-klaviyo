import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class KlaviyoBackInStockNotification extends Plugin {
    static options = {
        errorCls: 'has-error',
        validCls: 'is-valid',
        emailFieldSelector: '#email',
        subscribeToNewsletterSelector: '#subscribeToNewsletter',
        apiURL: 'https://a.klaviyo.com/client/back-in-stock-subscriptions',
        contentType: 'application/json',
        revision: '2024-10-15',
        hiddenCls: 'd-none',
        successMessageSelector: '.klaviyo-success',
        errorMessageSelector: '.klaviyo-error',
        errorVariantMessageSelector: '.klaviyo-error-variant',
        notValidEmailMessageSelector: '.klaviyo-email-not-valid',
        fetchHeaderAccept: "application/json",
        composerPluginVersion: null,
        dbPluginVersion: null,
        shopwareVersion: null
    };

    init() {
        this._client = new HttpClient();

        this._getFormDataElements();
        this.registerEvents();
    }

    _getFormDataElements() {
        this._email = DomAccess.querySelector(this.el, this.options.emailFieldSelector );
        this._subscribeToNewsletter = DomAccess.querySelector(this.el, this.options.subscribeToNewsletterSelector);
        this._successMessage = DomAccess.querySelector(this.el, this.options.successMessageSelector);
        this._errorMessage = DomAccess.querySelector(this.el, this.options.errorMessageSelector);
        this._emailNotValid = DomAccess.querySelector(this.el, this.options.notValidEmailMessageSelector);
        this._errorVariantMessage = DomAccess.querySelector(this.el, this.options.errorVariantMessageSelector);
        const composerPluginVersionEl = document.querySelector('[data-klaviyo-plugin-version]');
        const dbPluginVersionEl = document.querySelector('[data-klaviyo-plugin-version-db]');
        const shopwareVersionEl = document.querySelector('[data-klaviyo-shopware-version]');
        this.options.composerPluginVersion = composerPluginVersionEl ? composerPluginVersionEl.getAttribute('data-klaviyo-plugin-version') : "unknown";
        this.options.dbPluginVersion = dbPluginVersionEl ? dbPluginVersionEl.getAttribute('data-klaviyo-plugin-version-db') : "unknown";
        this.options.shopwareVersion = shopwareVersionEl ? shopwareVersionEl.getAttribute('data-klaviyo-shopware-version') : "unknown";
    }

    registerEvents() {
        this.el.addEventListener('submit', this.onSubmit.bind(this));
    }

    onSubmit(event) {
        event.preventDefault();
        if (this._validateEmail(this._email.value)) {
            return this._proceedSubscription();
        }

        return this._showEmailValidationErrorMessage();
    }

    async _proceedSubscription() {
        const data = this._createFormData();
        let productId;
        let email = data.get('email');

        if (data.get('variant')) {
            productId = data.get('variant');
        } else {
            productId = data.get('product');
        }

        let body = JSON.stringify({
            data: {
                type: 'back-in-stock-subscription',
                attributes: {
                    channels: ['EMAIL'],
                    profile: {
                        data: {
                            type: 'profile',
                            attributes: {
                                email: email
                            }
                        }
                    }
                },
                relationships: {
                    variant: {
                        data: {
                            type: 'catalog-variant',
                            id: '$custom:::$default:::' + productId
                        }
                    }
                }
            }
        });

        await fetch(this.options.apiURL + '/?company_id=' + this.options.publicApiKey, {
            "headers": {
                "accept": this.options.fetchHeaderAccept,
                "content-type": this.options.contentType,
                "revision": this.options.revision,
                "x-sw-plugin-version": this.options.composerPluginVersion,
                "x-sw-plugin-version-db": this.options.dbPluginVersion,
                "x-sw-version": this.options.shopwareVersion,
            },
            "body": body,
            "method": "POST",
        }).then(response => {
            if (data.get('subscribe_for_newsletter') === 'true') {
                this._proceedNewsletterSubscribe(email);
            }

            this._handleResponse(response);
        }).catch(err => {
            console.error(err);
        });
    }

    _handleResponse(response) {
        if (response.ok) {
            return this._showSuccessMessage();
        }

        if (!response.ok && response.status === 404) {
            return this._showErrorVariantMessage();
        }

        return this._showErrorMessage();
    }

    _proceedNewsletterSubscribe(email) {
        if (this._subscribeToNewsletter) {
            this._client.post(
                '/form/newsletter',
                JSON.stringify({
                    email: email,
                    option: 'subscribe',
                })
            );
        }
    }

    _showSuccessMessage() {
        this._email.value = '';
        this._errorMessage.classList.add(this.options.hiddenCls);
        this._emailNotValid.classList.add(this.options.hiddenCls);
        this._successMessage.classList.remove(this.options.hiddenCls);
    }

    _showErrorMessage() {
        this._errorMessage.classList.remove(this.options.hiddenCls);
        this._emailNotValid.classList.add(this.options.hiddenCls);
        this._successMessage.classList.add(this.options.hiddenCls);
    }

    _showErrorVariantMessage() {
        this._errorVariantMessage.classList.remove(this.options.hiddenCls);
        this._errorMessage.classList.add(this.options.hiddenCls);
        this._emailNotValid.classList.add(this.options.hiddenCls);
        this._successMessage.classList.add(this.options.hiddenCls);
    }

    _showEmailValidationErrorMessage() {
        this._errorMessage.classList.add(this.options.hiddenCls);
        this._emailNotValid.classList.remove(this.options.hiddenCls);
        this._successMessage.classList.add(this.options.hiddenCls);
    }

    _createFormData() {
        let data = new URLSearchParams();
        if (this.options.variantId !== this.options.productID) {
            this.options.productID = this.options.variantId;
        }

        data.append('a', this.options.publicApiKey);
        data.append('email', this._email.value);
        data.append('platform', 'api');
        data.append('variant', this.options.variantId);
        data.append('product', this.options.productID);
        data.append('subscribe_for_newsletter', this._subscribeToNewsletter.checked);
        return data;
    }

    _validateEmail(email) {
        let validFormat = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return validFormat.test(email)
    }
}
