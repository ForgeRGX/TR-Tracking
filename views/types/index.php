<?php /** @var array $types */ ?>
<div class="page-head d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><?= t('Tipos de activo') ?></h1>
    <a href="/types/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i><?= t('Nuevo tipo') ?></a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-stack">
            <thead><tr><th><?= t('Tipo') ?></th><th><?= t('Descripción') ?></th><th class="text-end"><?= t('Activos') ?></th><th class="text-end" data-col="actions"><?= t('Acciones') ?></th></tr></thead>
            <tbody>
            <?php if (!$types): ?>
                <tr><td colspan="4" class="text-center text-muted py-4"><?= t('No hay tipos de activo.') ?></td></tr>
            <?php endif; ?>
            <?php foreach ($types as $t): ?>
                <tr>
                    <td><?= type_badge($t['name'], (int) $t['id']) ?></td>
                    <td class="text-muted"><?= e($t['description'] ?? '') ?></td>
                    <td class="text-end">
                        <a href="<?= e(url('/assets', ['type_id' => $t['id']])) ?>"><?= (int) $t['assets_count'] ?></a>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/types/<?= (int) $t['id'] ?>/edit"><?= t('Editar') ?></a>
                        <?php if ((int) $t['assets_count'] > 0): ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled
                                    title="<?= e(t('Tiene activos asociados: no se puede eliminar')) ?>"><?= t('Eliminar') ?></button>
                        <?php else: ?>
                            <form method="post" action="/types/<?= (int) $t['id'] ?>/delete" class="d-inline"
                                  data-confirm="<?= e(t('¿Eliminar el tipo «%s»?', [$t['name']])) ?>">
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
<p class="text-muted small mt-2"><?= t('Un tipo con activos asociados no puede eliminarse. Los colores de Brokerage, Private Markets, Fixed Income y Crypto Wallet son fijos; el resto usan una paleta automática.') ?></p>
