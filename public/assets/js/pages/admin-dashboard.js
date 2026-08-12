/* Admin dashboard charts */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.dashboardData) return;

    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.color = '#475569';

    const palette = ['#2c6e52', '#0e7490', '#b45309', '#b91c1c', '#6d28d9', '#4f46e5', '#0f766e', '#a16207', '#be185d', '#4338ca'];

    const common = (data, type) => ({
        type,
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
        },
    });

    const D = window.dashboardData;

    if (document.getElementById('chartEmployment')) {
        new Chart(document.getElementById('chartEmployment'), common({
            labels: D.employmentStatus.labels,
            datasets: [{
                data: D.employmentStatus.data,
                backgroundColor: palette.slice(0, D.employmentStatus.labels.length),
                borderWidth: 0,
            }],
        }, 'doughnut'));
    }

    if (document.getElementById('chartEmploymentByYear')) {
        new Chart(document.getElementById('chartEmploymentByYear'), common({
            labels: D.employmentByYear.labels,
            datasets: [{
                label: 'Employment Rate (%)',
                data: D.employmentByYear.data,
                backgroundColor: 'rgba(44,110,82,.18)',
                borderColor: '#2c6e52',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#2c6e52',
            }],
        }, 'line'));
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
            datasets: [{
                data: D.sector.data,
                backgroundColor: palette.slice(0, D.sector.labels.length),
                borderWidth: 0,
            }],
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

    if (document.getElementById('chartTimeToEmployment')) {
        new Chart(document.getElementById('chartTimeToEmployment'), common({
            labels: D.timeToEmployment.labels,
            datasets: [{
                label: 'Graduates',
                data: D.timeToEmployment.data,
                backgroundColor: 'rgba(107,33,168,.18)',
                borderColor: '#6d28d9',
                borderWidth: 2,
                borderRadius: 6,
            }],
        }, 'bar'));
    }
});
