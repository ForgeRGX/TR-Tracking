// Confirmación antes de enviar formularios con data-confirm (p. ej. eliminar)
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
        e.preventDefault();
    }
});

// Etiquetas de columna para las tablas que se apilan en móvil (<768px)
document.querySelectorAll('table.table-stack').forEach(function (table) {
    var ths = table.querySelectorAll('thead th');
    var heads = Array.prototype.map.call(ths, function (th) { return th.textContent.trim(); });
    var kinds = Array.prototype.map.call(ths, function (th) { return th.getAttribute('data-col') || ''; });
    table.querySelectorAll('tbody tr, tfoot tr').forEach(function (tr) {
        var col = 0;
        Array.prototype.forEach.call(tr.children, function (td) {
            var span = td.colSpan || 1;
            if (span === 1 && heads[col]) { td.setAttribute('data-label', heads[col]); }
            if (span === 1 && kinds[col]) { td.setAttribute('data-col', kinds[col]); }
            col += span;
        });
    });
});
