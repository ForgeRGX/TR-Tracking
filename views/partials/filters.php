<?php
/**
 * @var array $filters      filtros normalizados
 * @var array $types        tipos de activo
 * @var array $assetOptions activos (id, name, type_name)
 * @var string $filterAction ruta del formulario
 */
?>
<form method="get" action="<?= e($filterAction) ?>" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-4">
            <label class="form-label small text-muted mb-1" for="f-q"><?= t('Buscar') ?></label>
            <input type="search" id="f-q" name="q" class="form-control" placeholder="<?= e(t('Activo, ticker, ISIN, notas…')) ?>"
                   value="<?= e($filters['q']) ?>">
        </div>
        <div class="col-6 col-lg-4">
            <label class="form-label small text-muted mb-1" for="f-type"><?= t('Tipo') ?></label>
            <select id="f-type" name="type_id" class="form-select">
                <option value=""><?= t('Todos') ?></option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (int) $filters['type_id'] === (int) $t['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-4">
            <label class="form-label small text-muted mb-1" for="f-asset"><?= t('Activo') ?></label>
            <select id="f-asset" name="asset_id" class="form-select">
                <option value=""><?= t('Todos') ?></option>
                <?php foreach ($assetOptions as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $filters['asset_id'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['name']) ?> — <?= e($a['type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-op"><?= t('Operación') ?></label>
            <select id="f-op" name="op" class="form-select">
                <option value=""><?= t('Todas') ?></option>
                <option value="buy" <?= $filters['op'] === 'buy' ? 'selected' : '' ?>><?= t('Compras') ?></option>
                <option value="sell" <?= $filters['op'] === 'sell' ? 'selected' : '' ?>><?= t('Ventas') ?></option>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-from"><?= t('Desde') ?></label>
            <input type="date" id="f-from" name="from" class="form-control" value="<?= e($filters['from']) ?>">
        </div>
        <div class="col-6 col-lg-3">
            <label class="form-label small text-muted mb-1" for="f-to"><?= t('Hasta') ?></label>
            <input type="date" id="f-to" name="to" class="form-control" value="<?= e($filters['to']) ?>">
        </div>
        <div class="col-6 col-lg-3 d-flex gap-2 filter-actions">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i><?= t('Filtrar') ?></button>
            <a class="btn btn-outline-secondary" href="<?= e($filterAction) ?>"><?= t('Limpiar') ?></a>
        </div>
    </div>
</form>
