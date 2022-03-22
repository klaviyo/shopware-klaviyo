import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class KlaviyoBackInStockEventTrackingComponent extends Plugin {
    static options = {
        snippets: {
            successMessage: '',
            failedMessage: '',
            exceptionMessage: ''
        }
    };

    init() {
        this.client = new HttpClient(window.accessKey, window.contextToken);

        this._registerEvents();
    }

    _registerEvents() {
        const that = this;

        this.el.addEventListener('submit', (event) => {
            that._onsubmit(event);
        });
    }

    _onsubmit(event) {
        event.preventDefault();

        const rawFormData = new FormData(event.target);

        this.formData = this._parseFormData(rawFormData);
        this.url = event.target.dataset.action;
        this._subscribe();
    }

    _subscribe() {
        const that = this;
        const snippets = that.options.snippets;

        fetch(this.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(
                this.formData
            )
        }).then(response => response.json())
            .then(function (data) {
                that._showFeedback(data.success, data.success ? snippets.successMessage : snippets.failedMessage);
            }).catch(error => {
            console.log(error);
            that._showFeedback(false, snippets.exceptionMessage);
        });
    }

    _showFeedback(success, message) {
        const messageContainer = this.el.querySelector('#message-container');
        const messageElement = messageContainer.querySelector('#message-label');

        messageElement.innerHTML = message;
        if (success) {
            messageContainer.classList.remove('alert-danger');
            messageContainer.classList.add('alert-success');
        } else {
            messageContainer.classList.remove('alert-success');
            messageContainer.classList.add('alert-danger');
        }
        messageContainer.classList.replace('d-none', 'd-flex');
    }

    _parseFormData(formData) {
        const postData = {};
        for (const entry of formData.entries()) {
            const input = entry[0].split(/(?:\[|\])/).filter(Boolean);

            if ( input.length > 2 ) {
                if ( !postData[input[0]] ) {
                    postData[input[0]] = {
                        [input[1]]: {}
                    };
                }

                postData[input[0]][input[1]][input[2]] = entry[1];
            } else {
                postData[input[0]] = entry[1]
            }
        }

        return postData;
    }
}