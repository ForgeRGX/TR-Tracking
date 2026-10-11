<?php
declare(strict_types=1);

/** Todas las consultas se limitan al usuario autenticado (Auth::id()). */
final class AssetType
{
    public const DEFAULTS = [
        ['Brokerage', 'Acciones, ETFs y otros valores cotizados'],
        ['Private Markets', 'Mercados privados: private equity, deuda privada, etc.'],
        ['Fixed Income', 'Bonos, letras y otros productos de renta fija'],
        ['Crypto Wallet', 'Criptoactivos'],
    ];

    public static function createDefaults(int $userId): void
    {
        foreach (self::DEFAULTS as [$name, $description]) {
            Database::query(
                'INSERT IGNORE INTO asset_types (user_id, name, description) VALUES (:u, :n, :d)',
                ['u' => $userId, 'n' => $name, 'd' => t($description)]
            );
        }
    }

    public static function all(): array
    {
        return Database::all(
            'SELECT t.*, (SELECT COUNT(*) FROM assets a WHERE a.asset_type_id = t.id AND a.user_id = t.user_id) AS assets_count
             FROM asset_types t WHERE t.user_id = :u ORDER BY t.name',
            ['u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT * FROM asset_types WHERE id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function create(string $name, ?string $description): int
    {
        return Database::insert(
            'INSERT INTO asset_types (user_id, name, description) VALUES (:u, :name, :description)',
            ['u' => Auth::id(), 'name' => $name, 'description' => $description]
        );
    }

    public static function update(int $id, string $name, ?string $description): void
    {
        Database::query(
            'UPDATE asset_types SET name = :name, description = :description WHERE id = :id AND user_id = :u',
            ['name' => $name, 'description' => $description, 'id' => $id, 'u' => Auth::id()]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM asset_types WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    public static function nameExists(string $name, ?int $exceptId = null): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM asset_types WHERE user_id = :u AND name = :name AND id <> :id',
            ['u' => Auth::id(), 'name' => $name, 'id' => $exceptId ?? 0]
        ) > 0;
    }

    public static function assetsCount(int $id): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM assets WHERE asset_type_id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }
}
