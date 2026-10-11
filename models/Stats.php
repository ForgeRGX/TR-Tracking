<?php
declare(strict_types=1);

/**
 * Estadísticas de cartera. Definiciones:
 *  - Comprado          = SUM(importe de las compras)
 *  - Vendido           = SUM(importe de las ventas)
 *  - Capital invertido = Comprado − Vendido (neto; puede ser negativo si se ha vendido más de lo invertido)
 *  - Comisiones        = SUM(comisión de compras y ventas)
 *  - Coste total       = Capital invertido + Comisiones (flujo de caja neto desembolsado)
 *  - Ganancias         = SUM(importe de cada venta − su comisión)
 * Los porcentajes se calculan sobre el capital invertido positivo (los activos con neto ≤ 0 cuentan 0 %).
 */
final class Stats
{
    private const NET = "CASE WHEN i.operation_type = 'sell' THEN -i.amount_eur ELSE i.amount_eur END";

    private const AGG = "SUM(CASE WHEN i.operation_type = 'buy' THEN i.amount_eur ELSE 0 END) AS bought,
                         SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur ELSE 0 END) AS sold,
                         SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur - i.commission_eur ELSE 0 END) AS gains,
                         SUM(CASE WHEN i.operation_type = 'sell' THEN -i.amount_eur ELSE i.amount_eur END) AS invested,
                         SUM(i.commission_eur) AS commissions,
                         SUM(CASE WHEN i.operation_type = 'sell' THEN -i.amount_eur ELSE i.amount_eur END) + SUM(i.commission_eur) AS total_cost,
                         COUNT(*) AS ops";

    public static function totals(array $f): array
    {
        [$w, $p] = Investment::where($f);
        return Database::one(
            "SELECT COALESCE(SUM(CASE WHEN i.operation_type = 'buy' THEN i.amount_eur ELSE 0 END), 0) AS bought,
                    COALESCE(SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur ELSE 0 END), 0) AS sold,
                    COALESCE(SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur - i.commission_eur ELSE 0 END), 0) AS gains,
                    COALESCE(SUM(" . self::NET . "), 0) AS invested,
                    COALESCE(SUM(i.commission_eur), 0) AS commissions,
                    COALESCE(SUM(" . self::NET . ") + SUM(i.commission_eur), 0) AS total_cost,
                    COUNT(*) AS ops,
                    COALESCE(SUM(i.operation_type = 'buy'), 0) AS buy_ops,
                    COALESCE(SUM(i.operation_type = 'sell'), 0) AS sell_ops,
                    COUNT(DISTINCT i.asset_id) AS assets,
                    MIN(i.purchase_date) AS first_date,
                    MAX(i.purchase_date) AS last_date "
            . Investment::FROM . " $w",
            $p
        );
    }

    public static function byType(array $f): array
    {
        [$w, $p] = Investment::where($f);
        $rows = Database::all(
            'SELECT t.id, t.name, ' . self::AGG . ', COUNT(DISTINCT a.id) AS assets '
            . Investment::FROM . " $w GROUP BY t.id, t.name ORDER BY invested DESC, t.name",
            $p
        );
        return self::addShare($rows);
    }

    public static function byAsset(array $f): array
    {
        [$w, $p] = Investment::where($f);
        $rows = Database::all(
            'SELECT a.id, a.name, t.id AS type_id, t.name AS type_name, ' . self::AGG . ' '
            . Investment::FROM . " $w GROUP BY a.id, a.name, t.id, t.name ORDER BY invested DESC, a.name",
            $p
        );
        return self::addShare($rows);
    }

    /**
     * Series mensuales (meses vacíos rellenados, hasta el mes actual):
     *  monthly / cumulative       = capital neto aportado (compras − ventas, sin comisiones)
     *  sales                      = ventas del mes
     *  gains / cumulative_gains   = ventas − comisión de venta, por mes y acumulado
     */
    public static function monthlySeries(array $f): array
    {
        [$w, $p] = Investment::where($f);
        $rows = Database::all(
            "SELECT DATE_FORMAT(i.purchase_date, '%Y-%m') AS ym,
                    SUM(" . self::NET . ") AS net,
                    SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur ELSE 0 END) AS sales,
                    SUM(CASE WHEN i.operation_type = 'sell' THEN i.amount_eur - i.commission_eur ELSE 0 END) AS gains "
            . Investment::FROM . " $w GROUP BY ym ORDER BY ym",
            $p
        );
        $empty = ['labels' => [], 'monthly' => [], 'cumulative' => [], 'sales' => [], 'gains' => [], 'cumulative_gains' => []];
        if (!$rows) {
            return $empty;
        }

        $cents = static fn($v): int => (int) round((float) $v * 100);
        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r['ym']] = ['net' => $cents($r['net']), 'sales' => $cents($r['sales']), 'gains' => $cents($r['gains'])];
        }
        $start = new DateTimeImmutable($rows[0]['ym'] . '-01');
        $lastRow = end($rows);
        $last = new DateTimeImmutable($lastRow['ym'] . '-01');
        $now = new DateTimeImmutable('first day of this month midnight');
        if ($now > $last) {
            $last = $now;
        }

        $out = $empty;
        $accNet = $accGains = 0;
        for ($d = $start; $d <= $last; $d = $d->modify('+1 month')) {
            $m = $byMonth[$d->format('Y-m')] ?? ['net' => 0, 'sales' => 0, 'gains' => 0];
            $accNet += $m['net'];
            $accGains += $m['gains'];
            $out['labels'][] = month_label($d);
            $out['monthly'][] = $m['net'] / 100;
            $out['cumulative'][] = $accNet / 100;
            $out['sales'][] = $m['sales'] / 100;
            $out['gains'][] = $m['gains'] / 100;
            $out['cumulative_gains'][] = $accGains / 100;
        }
        return $out;
    }

    private static function addShare(array $rows): array
    {
        $sum = array_sum(array_map(static fn($r) => max(0.0, (float) $r['invested']), $rows));
        foreach ($rows as &$r) {
            $r['share'] = $sum > 0 ? max(0.0, (float) $r['invested']) / $sum * 100 : 0.0;
        }
        unset($r);
        return $rows;
    }
}
