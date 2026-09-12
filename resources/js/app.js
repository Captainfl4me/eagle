import Chart from 'chart.js/auto';

/**
 * Dashboard charts: a pie chart of the current envelope allocation across
 * budgets and a grouped bar chart comparing budgeted vs realized amounts for
 * the latest month. Data is passed from the Blade template via data attributes
 * on each <canvas>, and colors are read from the app's CSS theme variables so
 * the charts follow the light/dark OS theme.
 */

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

function cssVar(name, fallback) {
    return getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim() || fallback;
}

function themeColors() {
    return {
        text: cssVar('--theme-text', '#0E2742'),
        muted: cssVar('--theme-muted', '#6b7280'),
        surface: cssVar('--theme-surface', '#ffffff'),
        line: cssVar('--theme-line', '#d1d5db'),
        primary: cssVar('--theme-primary', '#0484CD'),
        secondary: cssVar('--theme-secondary', '#065298'),
    };
}

// A stable, distinguishable palette for the pie slices (works on light & dark).
const SLICE_COLORS = [
    '#0484CD',
    '#065298',
    '#10b981',
    '#f59e0b',
    '#8b5cf6',
    '#ef4444',
    '#14b8a6',
    '#f97316',
    '#0ea5e9',
    '#84cc16',
];

function jsonAttr(el, name) {
    try {
        return JSON.parse(el.getAttribute(name));
    } catch {
        return null;
    }
}

function hasData(chartData) {
    return (
        Array.isArray(chartData?.labels) &&
        chartData.labels.length > 0 &&
        chartData.labels.some((_, i) => (chartData.values?.[i] ?? 0) !== 0)
    );
}

const charts = [];

function renderCharts() {
    // Destroy any previously mounted charts (e.g. when the OS theme flips).
    for (const chart of charts) {
        chart.destroy();
    }
    charts.length = 0;

    const colors = themeColors();

    // --- Pie: envelope allocation -------------------------------------------
    const pieCanvas = document.getElementById('pie-chart');
    if (pieCanvas && hasData({ labels: jsonAttr(pieCanvas, 'data-labels'), values: jsonAttr(pieCanvas, 'data-values') })) {
        const labels = jsonAttr(pieCanvas, 'data-labels');
        const values = jsonAttr(pieCanvas, 'data-values');

        charts.push(
            new Chart(pieCanvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: labels.map((_, i) => SLICE_COLORS[i % SLICE_COLORS.length]),
                            borderColor: colors.surface,
                            borderWidth: 2,
                            hoverOffset: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: colors.text, usePointStyle: true, boxWidth: 8 },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : '0.0';
                                    return ` ${context.label}: $${context.parsed.toFixed(2)} (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            }),
        );
    }

    // --- Cash flow: budgeted vs realized (latest month) ---------------------
    const cfCanvas = document.getElementById('cashflow-chart');
    if (cfCanvas) {
        const labels = jsonAttr(cfCanvas, 'data-labels') ?? [];
        const budgeted = jsonAttr(cfCanvas, 'data-budgeted') ?? [];
        const realized = jsonAttr(cfCanvas, 'data-realized') ?? [];

        charts.push(
            new Chart(cfCanvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Budgeted',
                            data: budgeted,
                            backgroundColor: colors.primary,
                            borderRadius: 4,
                            maxBarThickness: 28,
                        },
                        {
                            label: 'Realized',
                            data: realized,
                            backgroundColor: colors.secondary,
                            borderRadius: 4,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: colors.text, usePointStyle: true, boxWidth: 8 },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return ` ${context.dataset.label}: $${context.parsed.y.toFixed(2)}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: colors.muted },
                            grid: { display: false },
                        },
                        y: {
                            ticks: { color: colors.muted, callback: (v) => `$${v}` },
                            grid: { color: colors.line },
                        },
                    },
                },
            }),
        );
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCharts);
} else {
    renderCharts();
}

// Re-theme the charts when the OS switches between light and dark mode.
prefersDark.addEventListener('change', renderCharts);