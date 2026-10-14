<?php
/** @var array $list @var array $pending */
?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Tareas programadas') ?></h1>
    <a href="/scheduled/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i><?= t('Nueva tarea') ?>
    </a>
</div>

<?php if ($pending): ?>
<section class="mb-5">
    <h2 class="h5 mb-3">
        <i class="bi bi-bell-fill text-warning me-1"></i>
        <?= t('Pendientes de confirmación') ?>
        <span class="badge text-bg-warning ms-1"><?= count($pending) ?></span>
    </h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= t('Activo') ?></th>
                        <th><?= t('Fecha esperada') ?></th>
                        <th><?= t('Frecuencia') ?></th>
                        <th class="text-end"><?= t('Importe') ?></th>
                        <th class="text-end"><?= t('Comisión') ?></th>
                        <th><?= t('Fecha real de compra') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $occ): ?>
                    <tr>
                        <td>
                            <?= type_badge($occ['type_name'], (int) $occ['type_id']) ?>
                            <a href="/scheduled/<?= (int) $occ['scheduled_purchase_id'] ?>" class="fw-semibold text-decoration-none ms-1">
                                <?= e($occ['asset_name']) ?>
                            </a>
                        </td>
                        <td class="text-nowrap"><?= date_fmt($occ['expected_date']) ?></td>
                        <td class="small text-muted"><?= frequency_label($occ['frequency']) ?></td>
                        <td class="text-end text-nowrap"><?= money($occ['amount_eur']) ?></td>
                        <td class="text-end text-nowrap"><?= money($occ['commission_eur']) ?></td>
                        <td style="min-width:160px">
                            <form method="post" action="/scheduled/occurrences/<?= (int) $occ['id'] ?>/confirm" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="date" name="confirmed_date"
                                       value="<?= e($occ['expected_date']) ?>"
                                       class="form-control form-control-sm"
                                       required>
                                <button class="btn btn-sm btn-success text-nowrap" title="<?= e(t('Confirmar compra')) ?>">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="/scheduled/occurrences/<?= (int) $occ['id'] ?>/skip"
                                  data-confirm="<?= e(t('¿Omitir esta ocurrencia?')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary" title="<?= e(t('Omitir')) ?>">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted small mt-2">
        <i class="bi bi-info-circle"></i>
        <?= t('Introduce la fecha exacta en la que realizaste cada compra y pulsa el botón de confirmación. Si no realizaste la compra, pulsa el botón de omitir.') ?>
    </p>
</section>
<?php endif; ?>

<section>
    <h2 class="h5 mb-3"><?= t('Todas las tareas') ?></h2>
    <?php if (!$list): ?>
        <div class="card card-body text-center py-5 text-muted">
            <i class="bi bi-calendar-plus fs-1 mb-2 d-block"></i>
            <?= t('Aún no hay tareas programadas.') ?>
        </div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= t('Activo') ?></th>
                        <th><?= t('Frecuencia') ?></th>
                        <th class="text-end"><?= t('Importe') ?></th>
                        <th class="text-end"><?= t('Comisión') ?></th>
                        <th><?= t('Inicio') ?></th>
                        <th><?= t('Fin') ?></th>
                        <th><?= t('Estado') ?></th>
                        <th class="text-end"><?= t('Acciones') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $sp): ?>
                    <tr class="<?= $sp['is_active'] ? '' : 'table-secondary opacity-75' ?>">
                        <td>
                            <?= type_badge($sp['type_name'], 0) ?>
                            <a href="/scheduled/<?= (int) $sp['id'] ?>" class="fw-semibold text-decoration-none ms-1">
                                <?= e($sp['asset_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= frequency_label($sp['frequency']) ?></td>
                        <td class="text-end text-nowrap"><?= money($sp['amount_eur']) ?></td>
                        <td class="text-end text-nowrap"><?= money($sp['commission_eur']) ?></td>
                        <td class="text-nowrap"><?= date_fmt($sp['start_date']) ?></td>
                        <td class="text-nowrap text-muted small"><?= $sp['end_date'] ? date_fmt($sp['end_date']) : '—' ?></td>
                        <td>
                            <?php if ($sp['is_active']): ?>
                                <span class="badge text-bg-success"><?= t('Activa') ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary"><?= t('Pausada') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="/scheduled/<?= (int) $sp['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"
                               title="<?= e(t('Editar')) ?>">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="/scheduled/<?= (int) $sp['id'] ?>/toggle" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm <?= $sp['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                        title="<?= e($sp['is_active'] ? t('Pausar') : t('Activar')) ?>">
                                    <i class="bi <?= $sp['is_active'] ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                                </button>
                            </form>
                            <form method="post" action="/scheduled/<?= (int) $sp['id'] ?>/delete" class="d-inline"
                                  data-confirm="<?= e(t('¿Eliminar esta tarea programada? Las ocurrencias registradas como inversiones se conservarán.')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="<?= e(t('Eliminar')) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</section>
