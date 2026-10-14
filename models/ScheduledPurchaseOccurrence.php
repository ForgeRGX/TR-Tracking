<?php
declare(strict_types=1);

final class ScheduledPurchaseOccurrence
{
    /** Ocurrencias pendientes del usuario, ordenadas por fecha. */
    public static function pending(): array
    {
        return Database::all(
            "SELECT o.*, sp.amount_eur, sp.commission_eur, sp.frequency, sp.notes AS sp_notes,
                    a.name AS asset_name, a.id AS asset_id, t.name AS type_name, t.id AS type_id
               FROM scheduled_purchase_occurrences o
               JOIN scheduled_purchases sp ON sp.id = o.scheduled_purchase_id
               JOIN assets a ON a.id = sp.asset_id
               JOIN asset_types t ON t.id = a.asset_type_id
              WHERE o.user_id = :u AND o.status = 'pending'
              ORDER BY o.expected_date ASC",
            ['u' => Auth::id()]
        );
    }

    /** Historial de ocurrencias de una tarea concreta. */
    public static function forSchedule(int $spId): array
    {
        return Database::all(
            'SELECT o.*, i.purchase_date AS investment_date
               FROM scheduled_purchase_occurrences o
               LEFT JOIN investments i ON i.id = o.investment_id
              WHERE o.scheduled_purchase_id = :sp_id AND o.user_id = :u
              ORDER BY o.expected_date DESC',
            ['sp_id' => $spId, 'u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT o.*, sp.amount_eur, sp.commission_eur, sp.notes AS sp_notes,
                    a.name AS asset_name, a.id AS asset_id, sp.frequency
               FROM scheduled_purchase_occurrences o
               JOIN scheduled_purchases sp ON sp.id = o.scheduled_purchase_id
               JOIN assets a ON a.id = sp.asset_id
              WHERE o.id = :id AND o.user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    /** Número de ocurrencias pendientes del usuario (para badge en la nav). */
    public static function pendingCount(): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM scheduled_purchase_occurrences WHERE user_id = :u AND status = 'pending'",
            ['u' => Auth::id()]
        );
    }

    /**
     * Confirma una ocurrencia: crea la inversión y actualiza el estado.
     * Devuelve el ID de la inversión creada.
     */
    public static function confirm(int $id, string $confirmedDate): int
    {
        $occ = self::find($id);
        if (!$occ || $occ['status'] !== 'pending') {
            return 0;
        }

        $investmentId = Investment::create([
            'operation_type' => 'buy',
            'asset_id'       => $occ['asset_id'],
            'amount_eur'     => $occ['amount_eur'],
            'purchase_date'  => $confirmedDate,
            'commission_eur' => $occ['commission_eur'],
            'notes'          => $occ['sp_notes'],
        ]);

        Database::query(
            "UPDATE scheduled_purchase_occurrences
                SET status = 'confirmed', confirmed_date = :cd, investment_id = :inv
              WHERE id = :id AND user_id = :u",
            ['cd' => $confirmedDate, 'inv' => $investmentId, 'id' => $id, 'u' => Auth::id()]
        );

        return $investmentId;
    }

    public static function skip(int $id): void
    {
        Database::query(
            "UPDATE scheduled_purchase_occurrences SET status = 'skipped'
              WHERE id = :id AND user_id = :u AND status = 'pending'",
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    /** Totales de ocurrencias de una tarea (para mostrar en el detalle). */
    public static function statsForSchedule(int $spId): array
    {
        return Database::one(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'confirmed') AS confirmed,
                SUM(status = 'skipped')   AS skipped,
                SUM(status = 'pending')   AS pending
               FROM scheduled_purchase_occurrences
              WHERE scheduled_purchase_id = :sp_id AND user_id = :u",
            ['sp_id' => $spId, 'u' => Auth::id()]
        ) ?? ['total' => 0, 'confirmed' => 0, 'skipped' => 0, 'pending' => 0];
    }
}
