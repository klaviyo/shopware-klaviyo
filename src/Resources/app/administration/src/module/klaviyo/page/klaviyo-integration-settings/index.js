import template from './klaviyo-integration-settings.html.twig';
import './klaviyo-integration-settings.scss';

const {Component, Defaults} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings', {
    template,

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false
        };
    },

    created() {
        console.log('klaviyo-integration-settings create')
    },

    methods: {
        onSave() {
            this.isLoading = true;

            console.log('onSave')

        }
    }

});
