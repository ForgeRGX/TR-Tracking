<?php
/**
 * @var string $kind       'type' | 'asset'
 * @var array  $rows       filas de Stats::byType / Stats::byAsset
 * @var array  $totals     Stats::totals
 * @var bool   $showSales  mostrar columnas Vendido y Ganancias
 */
$showSales = $showSales ?? false;
?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-stack">
        <thead>
        <tr>
            <?php if ($kind === 'type'): ?><th><?= t('Tipo') ?></th><?php else: ?><th><?= t('Activo') ?></th><th><?= t('Tipo') ?></th><?php endif; ?>
            <th class="text-end"><?= t('Capital invertido') ?></th>
            <?php if ($showSales): ?>
                <th class="text-end"><?= t('Vendido') ?></th>
                <th class="text-end"><?= t('Ganancias') ?></th>
            <?php endif; ?>
            <th class="text-end"><?= t('Comisiones') ?></th>
            <th class="text-end"><?= t('Coste total') ?></th>
            <th style="min-width:150px"><?= t('% cartera') ?></th>
            <?php if ($kind === 'type'): ?><th class="text-end"><?= t('Activos') ?></th><?php endif; ?>
            <th class="text-end"><?= t('Operaciones') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="10" class="text-center text-muted py-4"><?= t('Sin datos todavía.') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $color = $kind === 'type'
                ? type_color($r['name'], (int) $r['id'])
                : type_color($r['type_name'], (int) $r['type_id']); ?>
            <tr>
                <?php if ($kind === 'type'): ?>
                    <td><?= type_badge($r['name'], (int) $r['id']) ?></td>
                <?php else: ?>
                    <td><a href="/assets/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a></td>
                    <td><?= type_badge($r['type_name'], (int) $r['type_id']) ?></td>
                <?php endif; ?>
                <td class="text-end text-nowrap"><?= e(money($r['invested'])) ?></td>
                <?php if ($showSales): ?>
                    <td class="text-end text-nowrap"><?= e(money($r['sold'])) ?></td>
                    <td class="text-end text-nowrap text-sell"><?= e(money($r['gains'])) ?></td>
                <?php endif; ?>
                <td class="text-end text-nowrap"><?= e(money($r['commissions'])) ?></td>
                <td class="text-end text-nowrap fw-semibold"><?= e(money($r['total_cost'])) ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px">
                            <div class="progress-bar" style="width:<?= number_format($r['share'], 2, '.', '') ?>%;background:<?= e($color) ?>"></div>
                        </div>
                        <span class="small text-nowrap"><?= e(pct($r['share'])) ?></span>
                    </div>
                </td>
                <?php if ($kind === 'type'): ?><td class="text-end"><?= (int) $r['assets'] ?></td><?php endif; ?>
                <td class="text-end"><?= (int) $r['ops'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($rows): ?>
            <tfoot>
            <tr class="fw-semibold">
                <td<?= $kind === 'asset' ? ' colspan="2"' : '' ?>><?= t('Total') ?></td>
                <td class="text-end text-nowrap"><?= e(money($totals['invested'])) ?></td>
                <?php if ($showSales): ?>
                    <td class="text-end text-nowrap"><?= e(money($totals['sold'])) ?></td>
                    <td class="text-end text-nowrap text-sell"><?= e(money($totals['gains'])) ?></td>
                <?php endif; ?>
                <td class="text-end text-nowrap"><?= e(money($totals['commissions'])) ?></td>
                <td class="text-end text-nowrap"><?= e(money($totals['total_cost'])) ?></td>
                <td><?= e(pct(100.0)) ?></td>
                <?php if ($kind === 'type'): ?><td class="text-end"><?= (int) $totals['assets'] ?></td><?php endif; ?>
                <td class="text-end"><?= (int) $totals['ops'] ?></td>
            </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>
