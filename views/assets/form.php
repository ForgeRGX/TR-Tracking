<?php
/** @var array $item @var array $errors @var ?int $id @var array $types */
$action = $id ? "/assets/$id" : '/assets';
?>
<h1 class="h3 mb-3"><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:720px">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-5 mb-3">
            <label class="form-label" for="asset_type_id"><?= t('Tipo de activo *') ?></label>
            <select id="asset_type_id" name="asset_type_id" class="form-select<?= invalid($errors, 'asset_type_id') ?>" required>
                <option value=""><?= t('Selecciona un tipo…') ?></option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (string) $item['asset_type_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= feedback($errors, 'asset_type_id') ?>
        </div>
        <div class="col-md-7 mb-3">
            <label class="form-label" for="name"><?= t('Nombre *') ?></label>
            <input type="text" id="name" name="name" maxlength="150" class="form-control<?= invalid($errors, 'name') ?>"
                   value="<?= e($item['name']) ?>" placeholder="<?= e(t('MSCI World')) ?>" required>
            <?= feedback($errors, 'name') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="ticker"><?= t('Ticker') ?></label>
            <input type="text" id="ticker" name="ticker" maxlength="20" class="form-control<?= invalid($errors, 'ticker') ?>" value="<?= e($item['ticker']) ?>">
            <?= feedback($errors, 'ticker') ?>
        </div>
        <div class="col-md-5 mb-3">
            <label class="form-label" for="isin"><?= t('ISIN') ?></label>
            <input type="text" id="isin" name="isin" maxlength="12" class="form-control<?= invalid($errors, 'isin') ?>" value="<?= e($item['isin']) ?>">
            <?= feedback($errors, 'isin') ?>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="currency"><?= t('Moneda') ?></label>
            <input type="text" id="currency" name="currency" maxlength="3" class="form-control<?= invalid($errors, 'currency') ?>" value="<?= e($item['currency']) ?>">
            <?= feedback($errors, 'currency') ?>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="description"><?= t('Descripción') ?></label>
        <textarea id="description" name="description" rows="3" class="form-control<?= invalid($errors, 'description') ?>"><?= e($item['description']) ?></textarea>
        <?= feedback($errors, 'description') ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary"><?= e(t($id ? 'Guardar cambios' : 'Crear activo')) ?></button>
        <a class="btn btn-outline-secondary" href="/assets"><?= t('Cancelar') ?></a>
    </div>
</form>
