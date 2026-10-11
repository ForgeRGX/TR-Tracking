<?php
/** @var array $asset @var array $totals @var array $rows */
$stats = [
    ['Total invertido', money($totals['invested'])],
    ['Total vendido', money($totals['sold'])],
    ['Ganancias', money($totals['gains'])],
    ['Comisiones', money($totals['commissions'])],
    ['Total desembolsado', money($totals['total_cost'])],
    ['Número de operaciones', (string) (int) $totals['ops']],
    ['Primera inversión', date_fmt($totals['first_date'])],
    ['Última inversión', date_fmt($totals['last_date'])],
];
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <a href="/assets" class="small text-muted text-decoration-none"><?= t('&larr; Activos') ?></a>
        <h1 class="h3 mb-1"><?= e($asset['name']) ?></h1>
        <div class="d-flex flex-wrap gap-3 align-items-center text-muted small">
            <?= type_badge($asset['type_name'], (int) $asset['asset_type_id']) ?>
            <span><?= t('Ticker:') ?> <strong><?= e($asset['ticker'] ?? '—') ?></strong></span>
            <span><?= t('ISIN:') ?> <strong><?= e($asset['isin'] ?? '—') ?></strong></span>
            <span><?= t('Moneda:') ?> <strong><?= e($asset['currency']) ?></strong></span>
        </div>
        <?php if (!empty($asset['description'])): ?><p class="mt-2 mb-0"><?= nl2br(e($asset['description'])) ?></p><?php endif; ?>
    </div>
    <div class="page-actions d-flex gap-2">
        <a class="btn btn-outline-primary" href="/assets/<?= (int) $asset['id'] ?>/edit"><?= t('Editar') ?></a>
        <?php partial('trade_buttons', ['assetId' => $asset['id']]); ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats as [$label, $value]): ?>
        <div class="col-6 col-md-3">
            <div class="card card-body h-100"><div class="text-muted small"><?= e(t($label)) ?></div><div class="fw-semibold fs-5"><?= e($value) ?></div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><?= t('Operaciones') ?></div>
    <div class="card-body p-0"><?php partial('investments_table', ['rows' => $rows, 'showAsset' => false]); ?></div>
</div>
