<?php
/** @var array $item @var array $errors @var ?int $id */
$action = $id ? "/types/$id" : '/types';
?>
<h1 class="h3 mb-3"><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="card card-body" novalidate style="max-width:640px">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name"><?= t('Nombre *') ?></label>
        <input type="text" id="name" name="name" maxlength="100" class="form-control<?= invalid($errors, 'name') ?>" value="<?= e($item['name']) ?>" required>
        <?= feedback($errors, 'name') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="description"><?= t('Descripción') ?></label>
        <input type="text" id="description" name="description" maxlength="255" class="form-control<?= invalid($errors, 'description') ?>" value="<?= e($item['description']) ?>">
        <?= feedback($errors, 'description') ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary"><?= e(t($id ? 'Guardar cambios' : 'Crear tipo')) ?></button>
        <a class="btn btn-outline-secondary" href="/types"><?= t('Cancelar') ?></a>
    </div>
</form>
