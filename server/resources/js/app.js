import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Chart from 'chart.js/auto';

Alpine.data('metricCharts', (initialData) => ({
    charts: {},

    init() {
        this.build(initialData);
    },

    destroy() {
        this.destroyAll();
    },

    update(data) {
        this.build(data);
    },

    build(data) {
        this.destroyAll();
        if (!data || !data.labels || data.labels.length === 0) return;

        this.line('cpuChart',  ['CPU %'],               [data.cpu_usage],                       ['#6366f1'],           data.labels);
        this.line('ramChart',  ['RAM %'],               [data.ram_pct],                         ['#14b8a6'],           data.labels);
        this.line('diskChart', ['Write KB/s', 'Read KB/s'], [data.disk_write_kb, data.disk_read_kb], ['#f59e0b', '#84cc16'], data.labels);
        this.line('netChart',  ['TX KB/s', 'RX KB/s'],  [data.net_tx_kb, data.net_rx_kb],       ['#3b82f6', '#ec4899'], data.labels);
        this.line('loadChart', ['Load 1m'],             [data.load_avg_1],                      ['#8b5cf6'],           data.labels);
    },

    destroyAll() {
        Object.values(this.charts).forEach(c => c.destroy());
        this.charts = {};
    },

    line(ref, seriesLabels, datasets, colors, xLabels) {
        const canvas = this.$refs[ref];
        if (!canvas) return;

        this.charts[ref] = new Chart(canvas, {
            type: 'line',
            data: {
                labels: xLabels,
                datasets: seriesLabels.map((label, i) => ({
                    label,
                    data: datasets[i],
                    borderColor: colors[i],
                    backgroundColor: colors[i] + '18',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0.3,
                    fill: true,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        display: seriesLabels.length > 1,
                        labels: { boxWidth: 12, font: { size: 11 } },
                    },
                },
                scales: {
                    x: {
                        ticks: { maxTicksLimit: 6, font: { size: 10 }, maxRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 } },
                        grid: { color: '#f3f4f6' },
                    },
                },
            },
        });
    },
}));

// Palette cycled across container series (one colour per container line).
const containerPalette = [
    '#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#3b82f6', '#84cc16',
    '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#a855f7', '#10b981',
];

Alpine.data('containerCharts', (initialData) => ({
    charts: {},

    init() {
        this.build(initialData);
    },

    destroy() {
        this.destroyAll();
    },

    update(data) {
        this.build(data);
    },

    build(data) {
        this.destroyAll();
        if (!data || !data.containers || data.containers.length === 0) return;

        this.multi('containerCpuChart', data.labels, data.containers, data.cpu);
        this.multi('containerMemChart', data.labels, data.containers, data.memory_mb);
    },

    destroyAll() {
        Object.values(this.charts).forEach(c => c.destroy());
        this.charts = {};
    },

    multi(ref, xLabels, names, seriesByName) {
        const canvas = this.$refs[ref];
        if (!canvas) return;

        this.charts[ref] = new Chart(canvas, {
            type: 'line',
            data: {
                labels: xLabels,
                datasets: names.map((name, i) => {
                    const color = containerPalette[i % containerPalette.length];
                    return {
                        label: name,
                        data: seriesByName[name] || [],
                        borderColor: color,
                        backgroundColor: color + '18',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        tension: 0.3,
                        fill: false,
                        spanGaps: true,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: { boxWidth: 12, font: { size: 11 } },
                    },
                },
                scales: {
                    x: {
                        ticks: { maxTicksLimit: 6, font: { size: 10 }, maxRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 } },
                        grid: { color: '#f3f4f6' },
                    },
                },
            },
        });
    },
}));

Livewire.start();
