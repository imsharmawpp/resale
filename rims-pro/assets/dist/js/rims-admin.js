/*! RIMS Pro admin bundle - Chart.js bootstrap + Kanban drag-and-drop */
(function () {
    'use strict';
    if (typeof window.Chart !== 'undefined') {
        var ctxs = {
            'rims-chart-sources': { type: 'doughnut', data: { labels: ['Web', 'WhatsApp', 'Phone', 'Referral'], datasets: [{ data: [12, 8, 5, 3], backgroundColor: ['#1e3a8a', '#16a34a', '#3b82f6', '#a855f7'] }] } },
            'rims-chart-funnel': { type: 'bar', data: { labels: ['New', 'Contacted', 'Interested', 'Visit', 'Negotiation', 'Closed', 'Lost'], datasets: [{ label: 'Leads', data: [22, 18, 14, 9, 6, 3, 4], backgroundColor: '#1e3a8a' }] }, options: { plugins: { legend: { display: false } } } },
            'rims-chart-monthly': { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], datasets: [{ label: 'Leads', data: [10, 14, 12, 18, 22, 28], borderColor: '#1e3a8a', tension: .3, fill: false }] } },
            'rims-chart-inventory': { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], datasets: [{ label: 'Inventory', data: [80, 96, 110, 105, 120, 132], borderColor: '#16a34a', tension: .3, fill: false }] } }
        };
        Object.keys(ctxs).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) new window.Chart(el.getContext('2d'), ctxs[id]);
        });
    }

    // Kanban drag-and-drop
    var dragged = null;
    document.querySelectorAll('.rims-kanban__card').forEach(function (card) {
        card.addEventListener('dragstart', function () { dragged = card; });
    });
    document.querySelectorAll('.rims-kanban__col').forEach(function (col) {
        col.addEventListener('dragover', function (e) { e.preventDefault(); });
        col.addEventListener('drop', function (e) {
            e.preventDefault();
            if (dragged) {
                col.appendChild(dragged);
                var stage = col.getAttribute('data-stage');
                var leadId = dragged.getAttribute('data-id');
                var f = dragged.querySelector('select');
                if (f) { f.value = stage; f.form.submit(); }
            }
        });
    });
})();
