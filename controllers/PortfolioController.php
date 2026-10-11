<?php
declare(strict_types=1);

final class PortfolioController extends BaseController
{
    public function index(): void
    {
        $f = Investment::filters($_GET);
        $this->render('portfolio/index', [
            'title'        => t('Portfolio'),
            'filters'      => $f,
            'types'        => AssetType::all(),
            'assetOptions' => Asset::options(),
            'totals'       => Stats::totals($f),
            'byType'       => Stats::byType($f),
            'byAsset'      => Stats::byAsset($f),
        ]);
    }
}
