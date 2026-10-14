<?php
/** @var array $item @var array $errors @var ?int $id @var array $assetOptions @var array $frequencies */
$action = $id ? "/scheduled/$id" : '/scheduled';
?>
<div class="d-flex align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0"><?= e($title) ?></h1>
</div>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:720px">
    <?= csrf_field() ?>

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

    <div class="mb-3">
        <label class="form-label" for="frequency"><?= t('Frecuencia *') ?></label>
        <select id="frequency" name="frequency" class="form-select<?= invalid($errors, 'frequency') ?>" required>
            <?php foreach ($frequencies as $f): ?>
                <option value="<?= e($f) ?>" <?= $item['frequency'] === $f ? 'selected' : '' ?>>
                    <?= frequency_label($f) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= feedback($errors, 'frequency') ?>
        <div class="form-text"><?= t('Los días laborables son lunes a viernes (sin festivos).') ?></div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="amount_eur"><?= t('Importe (€) *') ?></label>
            <input type="text" inputmode="decimal" id="amount_eur" name="amount_eur"
                   class="form-control<?= invalid($errors, 'amount_eur') ?>"
                   value="<?= e($item['amount_eur']) ?>"
                   placeholder="<?= e(t('500,00')) ?>" required>
            <?= feedback($errors, 'amount_eur') ?>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="commission_eur"><?= t('Comisión (€)') ?></label>
            <input type="text" inputmode="decimal" id="commission_eur" name="commission_eur"
                   class="form-control<?= invalid($errors, 'commission_eur') ?>"
                   value="<?= e($item['commission_eur']) ?>"
                   placeholder="<?= e(t('0,00')) ?>">
            <?= feedback($errors, 'commission_eur') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="start_date"><?= t('Fecha de inicio *') ?></label>
            <input type="date" id="start_date" name="start_date"
                   class="form-control<?= invalid($errors, 'start_date') ?>"
                   value="<?= e($item['start_date']) ?>" required>
            <?= feedback($errors, 'start_date') ?>
            <div class="form-text"><?= t('La primera ocurrencia se generará a partir de esta fecha.') ?></div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label" for="end_date"><?= t('Fecha de fin') ?></label>
            <input type="date" id="end_date" name="end_date"
                   class="form-control<?= invalid($errors, 'end_date') ?>"
                   value="<?= e($item['end_date'] ?? '') ?>">
            <?= feedback($errors, 'end_date') ?>
            <div class="form-text"><?= t('Opcional. Deja vacío para que no tenga fin.') ?></div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="notes"><?= t('Notas') ?></label>
        <textarea id="notes" name="notes" rows="3" class="form-control<?= invalid($errors, 'notes') ?>"><?= e($item['notes'] ?? '') ?></textarea>
        <?= feedback($errors, 'notes') ?>
        <div class="form-text"><?= t('Se copiará a cada inversión confirmada.') ?></div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><?= e(t($id ? 'Guardar cambios' : 'Crear tarea')) ?></button>
        <a class="btn btn-outline-secondary" href="/scheduled"><?= t('Cancelar') ?></a>
    </div>
</form>
