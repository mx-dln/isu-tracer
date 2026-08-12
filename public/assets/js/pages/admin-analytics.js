/* Admin analytics charts */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.analyticsData) return;

    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.color = '#475569';

    const palette = ['#2c6e52', '#0e7490', '#b45309', '#b91c1c', '#6d28d9', '#4f46e5', '#0f766e', '#a16207', '#be185d', '#4338ca', '#15803d', '#c026d3'];

    const common = (data, type) => ({
        type,
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
        },
    });

    const D = window.analyticsData;

    if (document.getElementById('chartStatus')) {
        new Chart(document.getElementById('chartStatus'), common({
            labels: D.status.labels,
            datasets: [{ data: D.status.data, backgroundColor: palette.slice(0, D.status.labels.length), borderWidth: 0 }],
        }, 'doughnut'));
    }

    if (document.getElementById('chartYear')) {
        new Chart(document.getElementById('chartYear'), common({
            labels: D.year.labels,
            datasets: [{
                label: 'Employment Rate (%)',
                data: D.year.data,
                backgroundColor: 'rgba(44,110,82,.18)',
                borderColor: '#2c6e52',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#2c6e52',
            }],
        }, 'line'));
    }

    if (document.getElementById('chartTime')) {
        new Chart(document.getElementById('chartTime'), common({
            labels: D.time.labels,
            datasets: [{
                label: 'Graduates',
                data: D.time.data,
                backgroundColor: 'rgba(107,33,168,.18)',
                borderColor: '#6d28d9',
                borderWidth: 2,
                borderRadius: 6,
            }],
        }, 'bar'));
    }

    if (document.getElementById('chartRelevance')) {
        new Chart(document.getElementById('chartRelevance'), common({
            labels: D.relevance.labels,
            datasets: [{
                label: 'Respondents',
                data: D.relevance.data,
                backgroundColor: ['#2c6e52', '#3d8a68', '#8cc4a8', '#e2e8f0', '#cbd5e1'],
                borderRadius: 6,
            }],
        }, 'bar'));
    }

    if (document.getElementById('chartSector')) {
        new Chart(document.getElementById('chartSector'), common({
            labels: D.sector.labels,
            datasets: [{ data: D.sector.data, backgroundColor: palette.slice(0, D.sector.labels.length), borderWidth: 0 }],
        }, 'pie'));
    }

    if (document.getElementById('chartCompetency')) {
        new Chart(document.getElementById('chartCompetency'), common({
            labels: D.competency.labels,
            datasets: [{
                label: 'Average Rating (1-5)',
                data: D.competency.data,
                backgroundColor: 'rgba(14,116,144,.18)',
                borderColor: '#0e7490',
                borderWidth: 2,
                tension: 0.3,
                pointBackgroundColor: '#0e7490',
            }],
        }, 'radar'));
    }
});
