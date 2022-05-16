import template from './od-scheduler-charts.html.twig';
import './od-scheduler-charts.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-scheduler-charts', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        'notification',
    ],

    props: {
        jobTypes: {
            type: Array,
            required: false,
            default: () => []
        },

        sortType: {
            type: String,
            required: true,
            default: () => 'status'
        }
    },

    data() {
        return {
            items: null,
            statisticDateRanges: {
                value: '30Days',
                options: {
                    '30Days': 30,
                    '14Days': 14,
                    '7Days': 7,
                    '24Hours': 24,
                    yesterday: 1,
                },
            },
            chartSeries: [],
            colors: {
                0: '#FF8C00',
                1: '#0044ee',
                2: '#9400D3',
                3: '#FFD700',
                4: '#008000',
                5: '#40E0D0',
                6: '#00BFFF',
                7: '#209d90',
                8: '#C71585',
                9: '#000000',
                10: '#F4A460'
            }
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },

        getTimeUnitInterval() {
            const statisticDateRange = this.statisticDateRanges.value;

            if (statisticDateRange === 'yesterday' || statisticDateRange === '24Hours') {
                return 'hour';
            }

            return 'day';
        },

        dateAgo() {
            const date = new Date();
            const selectedDateRange = this.statisticDateRanges.value;
            const dateRange = this.statisticDateRanges.options[selectedDateRange] ?? 0;

            if (selectedDateRange === '24Hours') {
                date.setHours(date.getHours() - dateRange);

                return date;
            }

            date.setDate(date.getDate() - dateRange);
            date.setHours(0, 0, 0, 0);

            return date;
        },

        chartOptionsCount() {
            return {
                title: {
                    text: 'Jobs',
                    style: {
                        fontSize: '16px',
                        fontWeight: '600',
                    },
                },
                xaxis: {
                    type: 'datetime',
                    min: this.dateAgo.getTime(),
                    labels: {
                        datetimeUTC: false,
                    },
                },
                yaxis: {
                    min: 0,
                    tickAmount: 3,
                    labels: {
                        formatter: (value) => {
                            return parseInt(value, 10);
                        },
                    },
                },
            };
        },
    },

    watch: {
        sortType() {
            this.initChartData();
        }
    },

    created() {
        this.initChartData();
    },

    methods: {
        initChartData() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.setLimit(999999);

            if (this.jobTypes !== []) {
                criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
            }

            return this.jobRepository.search(criteria, Shopware.Context.api).then(items => {
                this.items = items;
                if (this.sortType === 'status') {
                    this.createStatusChartSeries(items);
                } else if (this.sortType === 'type') {
                    this.createTypeChartSeries(items);
                }
            });
        },

        createTypeChartSeries(items) {
            this.chartSeries = this.typeCharts(items);

            for (const item of items) {
                let date = this.parseDate(item.createdAt);

                this.chartSeries.forEach((chart) => {
                    if (chart.name === item.name) {
                        let existingIndex = chart.data.findIndex(e => e.x === date);
                        if (existingIndex !== -1) {
                            chart.data[existingIndex].y = chart.data[existingIndex].y + 1;
                        } else {
                            chart.data.push({
                                x: date,
                                y: 1
                            })
                        }
                    }
                })
            }
        },

        createStatusChartSeries(items) {
            this.chartSeries = this.statusCharts()

            for (const item of items) {
                let date = this.parseDate(item.createdAt)

                if (item.status === 'succeed') {
                    let successData = this.chartSeries[0].data
                    let existingIndex = successData.findIndex(e => e.x === date);

                    if (existingIndex !== -1) {
                        successData[existingIndex].y = successData[existingIndex].y + 1;
                    } else {
                        successData.push({
                            x: date,
                            y: 1
                        })
                    }
                } else if (item.status === 'pending') {
                    let pendingData = this.chartSeries[2].data
                    let existingIndex = pendingData.findIndex(e => e.x === date);

                    if (existingIndex !== -1) {
                        pendingData[existingIndex].y = pendingData[existingIndex].y + 1;
                    } else {
                        pendingData.push({
                            x: date,
                            y: 1
                        })
                    }
                } else if (item.status === 'error') {
                    let errorData = this.chartSeries[1].data
                    let existingIndex = errorData.findIndex(e => e.x === date);

                    if (existingIndex !== -1) {
                        errorData[existingIndex].y = errorData[existingIndex].y + 1;
                    } else {
                        errorData.push({
                            x: date,
                            y: 1
                        })
                    }
                }
            }
        },

        getRandomColor() {
            let n = (Math.random() * 0xfffff * 1000000).toString(16);
            return '#' + n.slice(0, 6);
        },

        typeCharts(items) {
            let chartSeries = [];

            items.forEach((item, index) => {

                let type = chartSeries.find((chart) => {
                   return chart.name === item.name
                })

                if (!type) {
                    chartSeries.push({
                        name: item.name,
                        data: [],
                        color: this.colors[index] ? this.colors[index] : this.getRandomColor(index)
                    })
                }
            })


            return chartSeries;
        },

        statusCharts() {
            return [
                {
                    name: 'Succeed',
                    data: [],
                    color: '#37d046'
                },
                {
                    name: 'Failed',
                    data: [],
                    color: '#de294c'
                },
                {
                    name: 'Pending',
                    data: [],
                    color: '#d1d9e0'
                }
            ]
        },

        parseDate(date) {
            date = date.substring(0, date.lastIndexOf('T') + 1);
            const parsedDate = new Date(date.replace(/-/g, '/').replace('T', ' '));
            return parsedDate.valueOf();
        },

        onRefresh() {
            this.initChartData();
        },
    },

    beforeDestroy() {
        clearInterval(this.reloadInterval)
    },
});
