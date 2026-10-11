<?php
/** @var array $assets @var array $types @var string $q @var ?int $typeId */
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Activos') ?></h1>
    <a href="/assets/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i><?= t('Nuevo activo') ?></a>
</div>

<form method="get" action="/assets" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label small text-muted mb-1" for="q"><?= t('Buscar') ?></label>
            <input type="search" id="q" name="q" class="form-control" placeholder="<?= e(t('Nombre, ticker, ISIN o tipo…')) ?>" value="<?= e($q) ?>">
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <label class="form-label small text-muted mb-1" for="type_id"><?= t('Tipo') ?></label>
            <select id="type_id" name="type_id" class="form-select">
                <option value=""><?= t('Todos') ?></option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (int) $typeId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3 d-flex gap-2 filter-actions">
            <button class="btn btn-primary"><?= t('Filtrar') ?></button>
            <a class="btn btn-outline-secondary" href="/assets"><?= t('Limpiar') ?></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead>
            <tr>
                <th><?= t('Activo') ?></th><th><?= t('Tipo') ?></th><th><?= t('Ticker') ?></th><th><?= t('ISIN') ?></th><th><?= t('Moneda') ?></th>
                <th class="text-end"><?= t('Invertido') ?></th><th class="text-end"><?= t('Ops.') ?></th><th class="text-end" data-col="actions"><?= t('Acciones') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$assets): ?>
                <tr><td colspan="8" class="text-center text-muted py-4"><?= t('No se han encontrado activos.') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($assets as $a): ?>
                <tr>
                    <td><a href="/assets/<?= (int) $a['id'] ?>" class="fw-semibold"><?= e($a['name']) ?></a></td>
                    <td><?= type_badge($a['type_name'], (int) $a['asset_type_id']) ?></td>
                    <td><?= e($a['ticker'] ?? '—') ?></td>
                    <td class="text-nowrap"><?= e($a['isin'] ?? '—') ?></td>
                    <td><?= e($a['currency']) ?></td>
                    <td class="text-end text-nowrap"><?= e(money($a['invested'])) ?></td>
                    <td class="text-end"><?= (int) $a['ops'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/assets/<?= (int) $a['id'] ?>/edit"><?= t('Editar') ?></a>
                        <?php if ((int) $a['ops'] > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled
                                    title="<?= e(t('Tiene inversiones asociadas: no se puede eliminar')) ?>"><?= t('Eliminar') ?></button>
                        <?php else: ?>
                            <form method="post" action="/assets/<?= (int) $a['id'] ?>/delete" class="d-inline"
                                  data-confirm="<?= e(t('¿Eliminar el activo «%s»?', [$a['name']])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><?= t('Eliminar') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
