<?php
/** @var array $filters @var array $types @var array $assetOptions @var array $totals @var array $byType @var array $byAsset */
?>
<h1 class="h3 mb-3"><?= t('Portfolio') ?></h1>

<?php partial('filters', [
    'filters' => $filters, 'types' => $types, 'assetOptions' => $assetOptions, 'filterAction' => '/portfolio',
]); ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        [t('Capital invertido'), money($totals['invested'])],
        [t('Vendido'), money($totals['sold'])],
        [t('Ganancias'), money($totals['gains'])],
        [t('Comisiones'), money($totals['commissions'])],
        [t('Coste total'), money($totals['total_cost'])],
        [t('Operaciones'), (string) (int) $totals['ops']],
    ] as [$label, $value]): ?>
        <div class="col-6 col-md-4 col-lg-2"><div class="card card-body h-100"><div class="text-muted small"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-header"><?= t('Por tipo de activo') ?></div>
    <div class="card-body p-0"><?php partial('breakdown_table', ['kind' => 'type', 'rows' => $byType, 'totals' => $totals, 'showSales' => true]); ?></div>
</div>

<div class="card">
    <div class="card-header"><?= t('Por activo') ?></div>
    <div class="card-body p-0"><?php partial('breakdown_table', ['kind' => 'asset', 'rows' => $byAsset, 'totals' => $totals, 'showSales' => true]); ?></div>
</div>
<p class="text-muted small mt-2"><?= t('Ordenado por capital invertido (de mayor a menor). Porcentajes sobre el capital invertido de los datos filtrados.') ?></p>
