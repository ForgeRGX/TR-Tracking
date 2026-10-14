<?php
declare(strict_types=1);

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $f = Investment::filters([]);
        $totals = Stats::totals($f);
        $byType = Stats::byType($f);
        $byAsset = Stats::byAsset($f);
        $series = Stats::monthlySeries($f);

        $top = array_slice($byAsset, 0, 15);
        // El doughnut solo admite valores positivos: los tipos con capital neto <= 0 no se dibujan
        $typeRows = array_values(array_filter($byType, static fn($r) => (float) $r['invested'] > 0));
        $chart = [
            'types' => [
                'labels' => array_column($typeRows, 'name'),
                'values' => array_map(static fn($r) => (float) $r['invested'], $typeRows),
                'colors' => array_map(static fn($r) => type_color($r['name'], (int) $r['id']), $typeRows),
            ],
            'assets' => [
                'labels' => array_column($top, 'name'),
                'values' => array_map(static fn($r) => (float) $r['invested'], $top),
                'colors' => array_map(static fn($r) => type_color($r['type_name'], (int) $r['type_id']), $top),
            ],
            'series' => $series,
            'i18n'   => [
                'sales'            => t('Ventas'),
                'gains'            => t('Ganancias'),
                'cumulative_gains' => t('Ganancias acumuladas'),
            ],
        ];

        ScheduledPurchase::generatePendingOccurrences();
        $pending  = ScheduledPurchaseOccurrence::pendingCount();
        $upcoming = ScheduledPurchase::upcomingDates(5);

        $this->render('dashboard/index', [
            'title'            => t('Dashboard'),
            'withCharts'       => true,
            'totals'           => $totals,
            'byType'           => $byType,
            'byAsset'          => $byAsset,
            'chart'            => $chart,
            'pendingScheduled' => $pending,
            'upcoming'         => $upcoming,
        ]);
    }
}
