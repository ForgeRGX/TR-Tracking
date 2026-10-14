<?php
declare(strict_types=1);

final class ScheduledPurchase
{
    public const FREQUENCIES = [
        'weekly',
        'monthly_start',
        'monthly_mid',
        'bimonthly',
        'quarterly_start',
        'quarterly_mid',
    ];

    public static function all(): array
    {
        return Database::all(
            'SELECT sp.*, a.name AS asset_name, t.name AS type_name
               FROM scheduled_purchases sp
               JOIN assets a ON a.id = sp.asset_id
               JOIN asset_types t ON t.id = a.asset_type_id
              WHERE sp.user_id = :u
              ORDER BY sp.is_active DESC, sp.start_date DESC',
            ['u' => Auth::id()]
        );
    }

    public static function active(): array
    {
        return Database::all(
            'SELECT sp.*, a.name AS asset_name, t.name AS type_name
               FROM scheduled_purchases sp
               JOIN assets a ON a.id = sp.asset_id
               JOIN asset_types t ON t.id = a.asset_type_id
              WHERE sp.user_id = :u AND sp.is_active = 1
                AND (sp.end_date IS NULL OR sp.end_date >= CURDATE())
              ORDER BY sp.start_date',
            ['u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT sp.*, a.name AS asset_name, t.name AS type_name
               FROM scheduled_purchases sp
               JOIN assets a ON a.id = sp.asset_id
               JOIN asset_types t ON t.id = a.asset_type_id
              WHERE sp.id = :id AND sp.user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO scheduled_purchases
                (user_id, asset_id, frequency, amount_eur, commission_eur, start_date, end_date, is_active, notes)
             VALUES
                (:user_id, :asset_id, :frequency, :amount_eur, :commission_eur, :start_date, :end_date, 1, :notes)',
            self::bind($d) + ['user_id' => Auth::id()]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE scheduled_purchases
                SET asset_id = :asset_id, frequency = :frequency, amount_eur = :amount_eur,
                    commission_eur = :commission_eur, start_date = :start_date,
                    end_date = :end_date, notes = :notes
              WHERE id = :id AND user_id = :user_id',
            self::bind($d) + ['id' => $id, 'user_id' => Auth::id()]
        );
    }

    public static function toggleActive(int $id): void
    {
        Database::query(
            'UPDATE scheduled_purchases SET is_active = NOT is_active WHERE id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function delete(int $id): void
    {
        Database::query(
            'DELETE FROM scheduled_purchases WHERE id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    /**
     * Genera (inserta) las ocurrencias pendientes hasta hoy para todas las tareas activas del usuario.
     * Idempotente: la UNIQUE KEY evita duplicados.
     */
    public static function generatePendingOccurrences(): void
    {
        $today = date('Y-m-d');
        foreach (self::active() as $sp) {
            $limit = ($sp['end_date'] && $sp['end_date'] < $today) ? $sp['end_date'] : $today;
            $dates = scheduled_expected_dates($sp['start_date'], $sp['frequency'], $limit);
            foreach ($dates as $date) {
                // INSERT IGNORE aprovecha la UNIQUE KEY para no romper en duplicados
                Database::query(
                    "INSERT IGNORE INTO scheduled_purchase_occurrences
                        (scheduled_purchase_id, user_id, expected_date, status)
                     VALUES (:sp_id, :u, :date, 'pending')",
                    ['sp_id' => $sp['id'], 'u' => Auth::id(), 'date' => $date]
                );
            }
        }
    }

    /** Próximas N fechas esperadas de todas las tareas activas (para el widget del dashboard). */
    public static function upcomingDates(int $limit = 5): array
    {
        $today    = date('Y-m-d');
        $horizon  = date('Y-m-d', strtotime('+6 months'));
        $upcoming = [];

        foreach (self::active() as $sp) {
            $end   = ($sp['end_date'] && $sp['end_date'] < $horizon) ? $sp['end_date'] : $horizon;
            $dates = scheduled_expected_dates($today, $sp['frequency'], $end);
            // Solo fechas >= hoy que aún no estén confirmadas/omitidas
            foreach ($dates as $date) {
                if ($date >= $today) {
                    $upcoming[] = [
                        'date'        => $date,
                        'asset_name'  => $sp['asset_name'],
                        'amount_eur'  => $sp['amount_eur'],
                        'frequency'   => $sp['frequency'],
                        'sp_id'       => $sp['id'],
                    ];
                }
            }
        }

        usort($upcoming, static fn($a, $b) => strcmp($a['date'], $b['date']));
        return array_slice($upcoming, 0, $limit);
    }

    private static function bind(array $d): array
    {
        return [
            'asset_id'       => $d['asset_id'],
            'frequency'      => $d['frequency'],
            'amount_eur'     => $d['amount_eur'],
            'commission_eur' => $d['commission_eur'],
            'start_date'     => $d['start_date'],
            'end_date'       => $d['end_date'] ?: null,
            'notes'          => $d['notes'] ?: null,
        ];
    }
}
