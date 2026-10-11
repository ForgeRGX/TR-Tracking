<?php
/** Botones Comprar / Vender. @var ?int $assetId activo preseleccionado (opcional) */
$q = !empty($assetId) ? ['asset_id' => (int) $assetId] : [];
?>
<div class="page-actions d-flex gap-2">
    <a href="<?= e(url('/investments/buy', $q)) ?>" class="btn btn-primary"><i class="bi bi-cart-plus"></i><?= t('Comprar') ?></a>
    <a href="<?= e(url('/investments/sell', $q)) ?>" class="btn btn-sell"><i class="bi bi-cash-coin"></i><?= t('Vender') ?></a>
</div>
