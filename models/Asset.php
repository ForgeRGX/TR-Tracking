<?php
declare(strict_types=1);

/** Todas las consultas se limitan al usuario autenticado (Auth::id()). */
final class Asset
{
    /** Listado con búsqueda y filtro por tipo, incluyendo totales invertidos. */
    public static function search(string $q = '', ?int $typeId = null): array
    {
        $where = ['a.user_id = :uid'];
        $params = ['uid' => Auth::id()];
        if ($typeId) {
            $where[] = 'a.asset_type_id = :type_id';
            $params['type_id'] = $typeId;
        }
        if ($q !== '') {
            $like = like_escape($q);
            $where[] = '(a.name LIKE :q1 OR a.ticker LIKE :q2 OR a.isin LIKE :q3 OR t.name LIKE :q4)';
            foreach ([1, 2, 3, 4] as $i) {
                $params["q$i"] = $like;
            }
        }
        $sql = 'SELECT a.*, t.name AS type_name,
                       COALESCE(s.invested, 0) AS invested, COALESCE(s.ops, 0) AS ops
                FROM assets a
                JOIN asset_types t ON t.id = a.asset_type_id
                LEFT JOIN (
                    SELECT asset_id, SUM(CASE WHEN operation_type = \'sell\' THEN -amount_eur ELSE amount_eur END) AS invested, COUNT(*) AS ops
                    FROM investments WHERE user_id = :uid2 GROUP BY asset_id
                ) s ON s.asset_id = a.id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY t.name, a.name';
        $params['uid2'] = Auth::id();
        return Database::all($sql, $params);
    }

    /** Opciones para selectores: "Nombre — Tipo". */
    public static function options(): array
    {
        return Database::all(
            'SELECT a.id, a.name, t.id AS type_id, t.name AS type_name
             FROM assets a JOIN asset_types t ON t.id = a.asset_type_id
             WHERE a.user_id = :u ORDER BY t.name, a.name',
            ['u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT a.*, t.name AS type_name
             FROM assets a JOIN asset_types t ON t.id = a.asset_type_id
             WHERE a.id = :id AND a.user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO assets (user_id, asset_type_id, name, ticker, isin, currency, description)
             VALUES (:user_id, :asset_type_id, :name, :ticker, :isin, :currency, :description)',
            self::bind($d) + ['user_id' => Auth::id()]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE assets SET asset_type_id = :asset_type_id, name = :name, ticker = :ticker,
                    isin = :isin, currency = :currency, description = :description
             WHERE id = :id AND user_id = :user_id',
            self::bind($d) + ['id' => $id, 'user_id' => Auth::id()]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM assets WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    public static function existsInType(int $typeId, string $name, ?int $exceptId = null): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM assets WHERE user_id = :u AND asset_type_id = :t AND name = :n AND id <> :id',
            ['u' => Auth::id(), 't' => $typeId, 'n' => $name, 'id' => $exceptId ?? 0]
        ) > 0;
    }

    public static function investmentsCount(int $id): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM investments WHERE asset_id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    private static function bind(array $d): array
    {
        return [
            'asset_type_id' => $d['asset_type_id'],
            'name'          => $d['name'],
            'ticker'        => $d['ticker'],
            'isin'          => $d['isin'],
            'currency'      => $d['currency'],
            'description'   => $d['description'],
        ];
    }
}
