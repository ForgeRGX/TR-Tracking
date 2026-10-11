(function () {
    var el = document.getElementById('chart-data');
    if (!el || !window.Chart) { return; }
    var d = JSON.parse(el.textContent);
    var locale = document.documentElement.lang === 'en' ? 'en-GB' : 'es-ES';
    var eur = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' });
    var pct = new Intl.NumberFormat(locale, { maximumFractionDigits: 2 });
    var $ = function (id) { return document.getElementById(id); };
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#4b5563';
    Chart.defaults.borderColor = 'rgba(29,36,51,.07)';

    function doughnut(canvas, data) {
        if (!canvas || !data.values.length) { return; }
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: data.labels, datasets: [{ data: data.values, backgroundColor: data.colors, borderColor: 'rgba(255,255,255,.9)', borderWidth: 3 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (c) {
                        var total = c.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                        return ' ' + c.label + ': ' + eur.format(c.parsed) + ' (' + pct.format(c.parsed / total * 100) + ' %)';
                    } } }
                }
            }
        });
    }

    function bars(canvas, labels, values, colors, horizontal) {
        if (!canvas || !values.length) { return; }
        var axis = horizontal ? 'x' : 'y';
        var scales = {};
        scales[axis] = { beginAtZero: true, ticks: { callback: function (v) { return eur.format(v); } } };
        new Chart(canvas, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 4 }] },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true, maintainAspectRatio: false, scales: scales,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + eur.format(c.parsed[axis]); } } }
                }
            }
        });
    }

    doughnut($('chart-types'), d.types);
    bars($('chart-assets'), d.assets.labels, d.assets.values, d.assets.colors, true);

    if ($('chart-cumulative') && d.series.labels.length) {
        new Chart($('chart-cumulative'), {
            type: 'line',
            data: { labels: d.series.labels, datasets: [{
                data: d.series.cumulative, borderColor: '#0e7c7b', backgroundColor: 'rgba(14,124,123,.14)',
                fill: true, tension: .25, pointRadius: 3
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return eur.format(v); } } } },
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + eur.format(c.parsed.y); } } } }
            }
        });
    }
    // Aportación neta mensual: dorado si suma capital, rojo suave si las ventas superan a las compras
    bars($('chart-monthly'), d.series.labels, d.series.monthly,
        d.series.monthly.map(function (v) { return v < 0 ? '#c0594b' : '#d9a441'; }), false);

    // Ventas y ganancias (ventas − comisión) por mes + ganancias acumuladas
    if ($('chart-sales') && d.series.labels.length) {
        var L = d.i18n || {};
        new Chart($('chart-sales'), {
            type: 'bar',
            data: { labels: d.series.labels, datasets: [
                { type: 'bar', label: L.sales, data: d.series.sales, backgroundColor: '#d9a441', borderRadius: 4, order: 2 },
                { type: 'bar', label: L.gains, data: d.series.gains, backgroundColor: '#0e7c7b', borderRadius: 4, order: 2 },
                { type: 'line', label: L.cumulative_gains, data: d.series.cumulative_gains, borderColor: '#2f9e6b',
                  backgroundColor: '#2f9e6b', tension: .25, pointRadius: 3, order: 1 }
            ] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return eur.format(v); } } } },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + eur.format(c.parsed.y); } } }
                }
            }
        });
    }
})();
