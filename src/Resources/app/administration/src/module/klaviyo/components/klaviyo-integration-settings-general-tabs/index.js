import template from './klaviyo-integration-settings-general-tabs.html.twig';

const { Component } = Shopware;

Component.register('klaviyo-integration-settings-general-tabs', {
    template,

    props: {
        activeTab: {
            type: String,
            required: true,
        },
    },

    methods: {
        onTabChange(tab) {
            const activeTab = typeof tab === 'string' ? tab : tab?.name;
            this.$emit('tab-change', activeTab || 'general');
        },
    },
});
