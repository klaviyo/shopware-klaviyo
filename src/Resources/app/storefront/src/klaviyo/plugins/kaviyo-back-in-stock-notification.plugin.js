import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class KlaviyoBackInStockNotification extends Plugin {
    static options = {
        submitBtnSelector: '.btn-submit-stock-notification',
        errorCls: 'has-error',
        validCls: 'is-valid',
        emailFieldSelector: '#email',
        subscribeToNewsletterSelector: '#subscribeToNewsletter',
        apiURL: 'https://a.klaviyo.com/onsite/components/back-in-stock/subscribe',
        contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
        hiddenCls: 'd-none',
        successMessageSelector: '.klaviyo-success',
        errorMessageSelector: '.klaviyo-error',
        notValidEmailMessageSelector: '.klaviyo-email-not-valid',
        fetchHeaderAccept: "application/json, text/plain, */*"
    };

    init() {
        this._client = new HttpClient()

        this._getFormDataElements();
        this.registerEvents();
    }

    _getFormDataElements() {
        this._submitBtn = DomAccess.querySelector(this.el, this.options.submitBtnSelector);
        this._email = DomAccess.querySelector(this.el, this.options.emailFieldSelector );
        this._subscribeToNewsletter = DomAccess.querySelector(this.el, this.options.subscribeToNewsletterSelector);
        this._successMessage = DomAccess.querySelector(this.el, this.options.successMessageSelector);
        this._errorMessage = DomAccess.querySelector(this.el, this.options.errorMessageSelector);
        this._emailNotValid = DomAccess.querySelector(this.el, this.options.notValidEmailMessageSelector);
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

    _proceedSubscription() {
        const data = this._createFormData();

        fetch(this.options.apiURL, {
            "headers": {
                "accept": this.options.fetchHeaderAccept,
                "content-type": this.options.contentType,
            },
            "body": data,
            "method": "POST",
        }).then(response => {
            this._handleResponse(response)
        }).catch(err => {
            console.error(err);
        });
    }

    _handleResponse(response) {
        if (response.ok) {
            return this._showSuccessMessage();
        }

        return this._showErrorMessage();
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
