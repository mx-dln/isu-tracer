(function () {
    var el = document.getElementById('forecastChart');
    if (!el) return;

    var data = window.forecastData || null;
    if (!data || !data.labels) return;

    new Chart(el.getContext('2d'), {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Historical',
                    data: data.historical,
                    borderColor: '#1d4ed8',
                    backgroundColor: 'rgba(29, 78, 216, 0.08)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#1d4ed8',
                    spanGaps: true,
                    tension: 0.2,
                },
                {
                    label: 'Projected',
                    data: data.projected,
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15, 118, 110, 0.10)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 4,
                    pointBackgroundColor: '#0f766e',
                    spanGaps: true,
                    tension: 0.2,
                },
                {
                    label: 'Confidence Band',
                    data: data.lower,
                    borderColor: 'rgba(245, 158, 11, 0.4)',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    borderWidth: 0,
                    pointRadius: 0,
                    fill: false,
                    spanGaps: false,
                },
                {
                    label: 'Confidence Band (Upper)',
                    data: data.upper,
                    borderColor: 'rgba(245, 158, 11, 0.0)',
                    backgroundColor: 'rgba(245, 158, 11, 0.0)',
                    borderWidth: 0,
                    pointRadius: 0,
                    fill: '-1',
                    spanGaps: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function (v) { return v; } } },
            },
            plugins: {
                legend: { labels: { usePointStyle: true, boxWidth: 8 } },
            },
        },
    });
})();
