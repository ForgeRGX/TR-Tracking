<?php
/** @var array $item @var array $errors @var ?int $id @var string $operation 'buy'|'sell' @var array $assetOptions */
$sell = $operation === 'sell';
$action = $id ? "/investments/$id" : '/investments';
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0"><?= e($title) ?></h1>
    <?= operation_badge($operation) ?>
</div>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:720px">
    <?= csrf_field() ?>
    <input type="hidden" name="operation_type" value="<?= e($operation) ?>">
    <?php if ($sell): ?>
        <div class="alert alert-info py-2 small"><?= t('Una venta resta del capital invertido. La ganancia es el importe vendido menos la comisión.') ?></div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label" for="asset_id"><?= t('Activo *') ?></label>
        <select id="asset_id" name="asset_id" class="form-select<?= invalid($errors, 'asset_id') ?>" required>
            <option value=""><?= t('Selecciona un activo…') ?></option>
            <?php foreach ($assetOptions as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= (string) $item['asset_id'] === (string) $a['id'] ? 'selected' : '' ?>>
                    <?= e($a['name']) ?> — <?= e($a['type_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= feedback($errors, 'asset_id') ?>
        <?php if (!$assetOptions): ?>
            <div class="form-text text-danger"><?= t('Aún no hay activos.') ?> <a href="/assets/create"><?= t('Crea uno primero') ?></a>.</div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="amount_eur"><?= t($sell ? 'Importe vendido (€) *' : 'Importe (€) *') ?></label>
            <input type="text" inputmode="decimal" id="amount_eur" name="amount_eur"
                   class="form-control<?= invalid($errors, 'amount_eur') ?>" value="<?= e($item['amount_eur']) ?>"
                   placeholder="<?= e(t('500,00')) ?>" required>
            <?= feedback($errors, 'amount_eur') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="purchase_date"><?= t($sell ? 'Fecha de venta *' : 'Fecha de compra *') ?></label>
            <input type="date" id="purchase_date" name="purchase_date"
                   class="form-control<?= invalid($errors, 'purchase_date') ?>" value="<?= e($item['purchase_date']) ?>" required>
            <?= feedback($errors, 'purchase_date') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="commission_eur"><?= t('Comisión (€)') ?></label>
            <input type="text" inputmode="decimal" id="commission_eur" name="commission_eur"
                   class="form-control<?= invalid($errors, 'commission_eur') ?>" value="<?= e($item['commission_eur']) ?>"
                   placeholder="<?= e(t('0,00')) ?>">
            <?= feedback($errors, 'commission_eur') ?>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="notes"><?= t('Notas') ?></label>
        <textarea id="notes" name="notes" rows="3" class="form-control<?= invalid($errors, 'notes') ?>"><?= e($item['notes']) ?></textarea>
        <?= feedback($errors, 'notes') ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn <?= $sell ? 'btn-sell' : 'btn-primary' ?>"><?= e(t($id ? 'Guardar cambios' : ($sell ? 'Registrar venta' : 'Registrar inversión'))) ?></button>
        <a class="btn btn-outline-secondary" href="/investments"><?= t('Cancelar') ?></a>
    </div>
</form>
