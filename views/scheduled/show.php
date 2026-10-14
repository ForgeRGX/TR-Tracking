<?php
/** @var array $sp @var array $occ @var array $stats */
?>
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <h1 class="h4 mb-0">
        <?= e($sp['asset_name']) ?>
        <span class="fw-normal text-muted">— <?= frequency_label($sp['frequency']) ?></span>
    </h1>
    <?php if ($sp['is_active']): ?>
        <span class="badge text-bg-success"><?= t('Activa') ?></span>
    <?php else: ?>
        <span class="badge text-bg-secondary"><?= t('Pausada') ?></span>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card stat-primary h-100">
            <div class="card-body">
                <div class="stat-label"><?= t('Importe por compra') ?></div>
                <div class="stat-value"><?= money($sp['amount_eur']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card stat-secondary h-100">
            <div class="card-body">
                <div class="stat-label"><?= t('Comisión por compra') ?></div>
                <div class="stat-value"><?= money($sp['commission_eur']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card stat-warning h-100">
            <div class="card-body">
                <div class="stat-label"><?= t('Pendientes') ?></div>
                <div class="stat-value"><?= (int) $stats['pending'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card stat-success h-100">
            <div class="card-body">
                <div class="stat-label"><?= t('Confirmadas') ?></div>
                <div class="stat-value"><?= (int) $stats['confirmed'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label"><?= t('Omitidas') ?></div>
                <div class="stat-value"><?= (int) $stats['skipped'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <dl class="row mb-0 small">
            <dt class="col-5 text-muted"><?= t('Activo') ?></dt>
            <dd class="col-7"><?= e($sp['asset_name']) ?></dd>
            <dt class="col-5 text-muted"><?= t('Frecuencia') ?></dt>
            <dd class="col-7"><?= frequency_label($sp['frequency']) ?></dd>
            <dt class="col-5 text-muted"><?= t('Fecha de inicio') ?></dt>
            <dd class="col-7"><?= date_fmt($sp['start_date']) ?></dd>
            <dt class="col-5 text-muted"><?= t('Fecha de fin') ?></dt>
            <dd class="col-7"><?= $sp['end_date'] ? date_fmt($sp['end_date']) : '—' ?></dd>
            <?php if ($sp['notes']): ?>
            <dt class="col-5 text-muted"><?= t('Notas') ?></dt>
            <dd class="col-7"><?= e($sp['notes']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
    <div class="col-md-6 d-flex gap-2 align-items-start flex-wrap">
        <a href="/scheduled/<?= (int) $sp['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i><?= t('Editar') ?>
        </a>
        <form method="post" action="/scheduled/<?= (int) $sp['id'] ?>/toggle">
            <?= csrf_field() ?>
            <button class="btn btn-sm <?= $sp['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                <i class="bi <?= $sp['is_active'] ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                <?= $sp['is_active'] ? t('Pausar') : t('Activar') ?>
            </button>
        </form>
        <a href="/scheduled" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i><?= t('Volver') ?>
        </a>
    </div>
</div>

<h2 class="h5 mb-3"><?= t('Historial de ocurrencias') ?></h2>
<?php if (!$occ): ?>
    <div class="card card-body text-center py-4 text-muted">
        <?= t('Aún no se han generado ocurrencias para esta tarea.') ?>
    </div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th><?= t('Fecha esperada') ?></th>
                    <th><?= t('Estado') ?></th>
                    <th><?= t('Fecha de compra') ?></th>
                    <th><?= t('Inversión') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($occ as $o): ?>
                <tr>
                    <td><?= date_fmt($o['expected_date']) ?></td>
                    <td>
                        <?php if ($o['status'] === 'confirmed'): ?>
                            <span class="badge text-bg-success"><?= t('Confirmada') ?></span>
                        <?php elseif ($o['status'] === 'skipped'): ?>
                            <span class="badge text-bg-secondary"><?= t('Omitida') ?></span>
                        <?php else: ?>
                            <span class="badge text-bg-warning"><?= t('Pendiente') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $o['confirmed_date'] ? date_fmt($o['confirmed_date']) : '—' ?></td>
                    <td>
                        <?php if ($o['investment_id']): ?>
                            <a href="/investments/<?= (int) $o['investment_id'] ?>/edit" class="small">
                                #<?= (int) $o['investment_id'] ?>
                            </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
