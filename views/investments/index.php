<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var array $filters @var array $types
 *  @var array $assetOptions @var array $totals @var array $query */
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Inversiones') ?></h1>
    <?php partial('trade_buttons'); ?>
</div>

<?php partial('filters', [
    'filters' => $filters, 'types' => $types, 'assetOptions' => $assetOptions, 'filterAction' => '/investments',
]); ?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2 small">
        <span><?= (int) $total ?> <?= t('operación(es)') ?></span>
        <span>
            <?= t('Capital') ?> <strong><?= e(money($totals['invested'])) ?></strong> ·
            <?= t('Vendido') ?> <strong><?= e(money($totals['sold'])) ?></strong> ·
            <?= t('Ganancias') ?> <strong><?= e(money($totals['gains'])) ?></strong> ·
            <?= t('Comisiones') ?> <strong><?= e(money($totals['commissions'])) ?></strong>
        </span>
    </div>
    <div class="card-body p-0"><?php partial('investments_table', ['rows' => $rows, 'showAsset' => true]); ?></div>
</div>

<div class="mt-3"><?php partial('pagination', ['page' => $page, 'pages' => $pages, 'query' => $query, 'basePath' => '/investments']); ?></div>
