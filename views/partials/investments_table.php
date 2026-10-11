<?php
/** @var array $rows @var bool $showAsset */
$showAsset = $showAsset ?? true;
?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-stack">
        <thead>
        <tr>
            <th><?= t('Fecha') ?></th>
            <th><?= t('Operación') ?></th>
            <?php if ($showAsset): ?><th><?= t('Tipo') ?></th><th><?= t('Activo') ?></th><?php endif; ?>
            <th class="text-end"><?= t('Importe') ?></th>
            <th class="text-end"><?= t('Comisión') ?></th>
            <th class="text-end"><?= t('Total') ?></th>
            <th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?= t('No hay inversiones que mostrar.') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): $sell = $r['operation_type'] === 'sell'; ?>
            <tr>
                <td class="text-nowrap"><?= e(date_fmt($r['purchase_date'])) ?></td>
                <td><?= operation_badge($r['operation_type']) ?></td>
                <?php if ($showAsset): ?>
                    <td><?= type_badge($r['type_name'], (int) $r['type_id']) ?></td>
                    <td><a href="/assets/<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a>
                        <?php if (!empty($r['notes'])): ?>
                            <i class="bi bi-chat-left-text text-muted ms-1" title="<?= e($r['notes']) ?>"></i>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <td class="text-end text-nowrap"><?= e(money($r['amount_eur'])) ?></td>
                <td class="text-end text-nowrap"><?= e(money($r['commission_eur'])) ?></td>
                <td class="text-end text-nowrap fw-semibold<?= $sell ? ' text-sell' : '' ?>"><?= e(money($r['total_eur'])) ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="/investments/<?= (int) $r['id'] ?>/edit"><?= t('Editar') ?></a>
                    <form method="post" action="/investments/<?= (int) $r['id'] ?>/delete" class="d-inline"
                          data-confirm="<?= e(t($sell ? '¿Eliminar esta venta? Esta acción no se puede deshacer.' : '¿Eliminar esta compra? Esta acción no se puede deshacer.')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><?= t('Eliminar') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="table-note"><?= t('Total: compra = importe + comisión · venta = importe − comisión (lo que se gana).') ?></p>
