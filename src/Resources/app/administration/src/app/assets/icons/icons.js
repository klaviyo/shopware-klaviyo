import { h } from 'vue';

const svgModules = import.meta.glob('./svg/*.svg', { as: 'raw', eager: true });

const components = Object.entries(svgModules).reduce((accumulator, [filePath, svgContent]) => {
    const componentName = filePath.split('/').pop().split('.')[0];

    const component = {
        name: componentName,
        functional: true, 
        render() {
            return h('span', {
                class: this.$attrs.class,
                style: this.$attrs.style,
                attrs: this.$attrs,
                on: this.$listeners,
                innerHTML: svgContent,
            });
        },
    };

    accumulator.push(component);
    return accumulator;
}, []);

export default components;