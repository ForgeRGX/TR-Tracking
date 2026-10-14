<?php
/** @var array $totals @var array $byType @var array $byAsset @var array $chart */
$cards = [
    [t('Total invertido'), money($totals['invested']), 'bi-wallet2', 'primary', t('Compras − ventas')],
    [t('Total vendido'), money($totals['sold']), 'bi-cash-coin', 'warning', t('Suma de ventas')],
    [t('Ganancias'), money($totals['gains']), 'bi-graph-up-arrow', 'success', t('Ventas − comisiones de venta')],
    [t('Total comisiones'), money($totals['commissions']), 'bi-receipt', 'secondary', t('Compras y ventas')],
    [t('Total desembolsado'), money($totals['total_cost']), 'bi-cash-stack', 'info', t('Invertido + comisiones')],
    [t('Nº de operaciones'), (string) (int) $totals['ops'], 'bi-list-check', 'secondary',
        t('%d compras · %d ventas', [(int) $totals['buy_ops'], (int) $totals['sell_ops']])],
    [t('Nº de activos'), (string) (int) $totals['assets'], 'bi-collection', 'secondary', t('Activos con operaciones')],
];
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Dashboard') ?></h1>
    <?php partial('trade_buttons'); ?>
</div>

<div class="row g-3 mb-2">
    <?php foreach ($cards as $i => [$label, $value, $icon, $color, $hint]): ?>
        <div class="col-6 col-md-3">
            <div class="card stat-card stat-<?= e($color) ?><?= $i === 0 ? ' stat-feature' : '' ?> h-100">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi <?= e($icon) ?>"></i></span>
                    <div class="stat-label"><?= e($label) ?></div>
                    <div class="stat-value"><?= e($value) ?></div>
                    <div class="stat-hint"><?= e($hint) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="text-muted small mb-4">
    <?= t('Capital invertido = compras − ventas · Coste total = capital invertido + comisiones · Ganancias = ventas − comisión de cada venta (no se compara con el coste de compra). Los porcentajes se calculan sobre el capital invertido. No se muestra valor de mercado.') ?>
</p>

<?php if ((int) $totals['ops'] === 0): ?>
    <div class="card card-body text-center py-5">
        <p class="mb-3"><?= t('Aún no hay inversiones registradas.') ?></p>
        <div class="d-flex justify-content-center"><?php partial('trade_buttons'); ?></div>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><?= t('Distribución por tipo de activo') ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-types"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><?= t('Detalle por tipo') ?></div>
                <div class="card-body p-0"><?php partial('breakdown_table', ['kind' => 'type', 'rows' => $byType, 'totals' => $totals]); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><?= t('Distribución por activo') ?> <span class="text-muted small"><?= t('(gráfico: top 15)') ?></span></div>
                <div class="card-body">
                    <div class="chart-box" style="height:<?= max(240, count($chart['assets']['labels']) * 34) ?>px"><canvas id="chart-assets"></canvas></div>
                </div>
                <div class="card-body p-0 border-top"><?php partial('breakdown_table', ['kind' => 'asset', 'rows' => $byAsset, 'totals' => $totals, 'showSales' => true]); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= t('Capital invertido acumulado por mes') ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-cumulative"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= t('Aportación mensual') ?></div>
                <div class="card-body"><div class="chart-box"><canvas id="chart-monthly"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><?= t('Ventas y ganancias por mes') ?></div>
                <div class="card-body">
                    <?php if ((int) $totals['sell_ops'] === 0): ?>
                        <p class="text-muted text-center my-4"><?= t('Todavía no has registrado ventas.') ?></p>
                    <?php else: ?>
                        <div class="chart-box"><canvas id="chart-sales"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted small mt-2"><?= t('Los gráficos mensuales usan la fecha de operación. El capital y la aportación son netos (compras − ventas, sin comisiones); las ganancias son ventas menos su comisión.') ?></p>

    <script type="application/json" id="chart-data"><?= json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

<?php if ($pendingScheduled > 0 || $upcoming): ?>
<div class="row g-4 mt-0">
    <?php if ($pendingScheduled > 0): ?>
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-bell-fill fs-5"></i>
            <span>
                <?= t('Tienes %d compra(s) programada(s) pendiente(s) de confirmación.', [$pendingScheduled]) ?>
                <a href="/scheduled" class="alert-link ms-2"><?= t('Revisar') ?> &rarr;</a>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($upcoming): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-1"></i><?= t('Próximas compras programadas') ?></span>
                <a href="/scheduled" class="btn btn-sm btn-outline-secondary"><?= t('Ver todas') ?></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th><?= t('Fecha esperada') ?></th>
                            <th><?= t('Activo') ?></th>
                            <th><?= t('Frecuencia') ?></th>
                            <th class="text-end"><?= t('Importe') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $u): ?>
                        <tr>
                            <td class="text-nowrap"><?= date_fmt($u['date']) ?></td>
                            <td>
                                <a href="/scheduled/<?= (int) $u['sp_id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= e($u['asset_name']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= frequency_label($u['frequency']) ?></td>
                            <td class="text-end text-nowrap"><?= money($u['amount_eur']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
