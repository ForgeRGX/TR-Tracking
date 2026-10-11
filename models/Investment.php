<?php
declare(strict_types=1);

final class Investment
{
    /** Base de todas las consultas filtrables (listado, totales, agrupaciones). */
    public const FROM = 'FROM investments i
                         JOIN assets a ON a.id = i.asset_id
                         JOIN asset_types t ON t.id = a.asset_type_id';

    /** Normaliza y valida los filtros recibidos por GET. */
    public static function filters(array $src): array
    {
        $int = static function (mixed $v): ?int {
            $r = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            return $r === false ? null : $r;
        };
        return [
            'type_id'  => $int($src['type_id'] ?? null),
            'asset_id' => $int($src['asset_id'] ?? null),
            'op'       => in_array($src['op'] ?? null, ['buy', 'sell'], true) ? $src['op'] : null,
            'from'     => parse_date((string) ($src['from'] ?? '')),
            'to'       => parse_date((string) ($src['to'] ?? '')),
            'q'        => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
        ];
    }

    /** Filtros con valor, para conservarlos en enlaces (paginación, etc.). */
    public static function queryParams(array $f): array
    {
        return array_filter($f, static fn($v) => $v !== null && $v !== '');
    }

    /** @return array{0:string,1:array} cláusula WHERE y parámetros */
    public static function where(array $f): array
    {
        $w = ['i.user_id = :uid'];
        $p = ['uid' => Auth::id()];
        if (!empty($f['type_id'])) {
            $w[] = 't.id = :type_id';
            $p['type_id'] = $f['type_id'];
        }
        if (!empty($f['asset_id'])) {
            $w[] = 'a.id = :asset_id';
            $p['asset_id'] = $f['asset_id'];
        }
        if (!empty($f['op'])) {
            $w[] = 'i.operation_type = :op';
            $p['op'] = $f['op'];
        }
        if (!empty($f['from'])) {
            $w[] = 'i.purchase_date >= :date_from';
            $p['date_from'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $w[] = 'i.purchase_date <= :date_to';
            $p['date_to'] = $f['to'];
        }
        if (($f['q'] ?? '') !== '') {
            $w[] = '(a.name LIKE :q1 OR a.ticker LIKE :q2 OR a.isin LIKE :q3 OR t.name LIKE :q4 OR i.notes LIKE :q5)';
            $like = like_escape($f['q']);
            foreach ([1, 2, 3, 4, 5] as $n) {
                $p["q$n"] = $like;
            }
        }
        return ['WHERE ' . implode(' AND ', $w), $p];
    }

    /** total_eur: compra = importe + comisión (lo que sale); venta = importe − comisión (lo que se gana). */
    private const SELECT = "SELECT i.*, a.name AS asset_name, t.id AS type_id, t.name AS type_name,
                                   CASE WHEN i.operation_type = 'sell'
                                        THEN i.amount_eur - i.commission_eur
                                        ELSE i.amount_eur + i.commission_eur END AS total_eur";

    public static function paginate(array $f, int $page, int $perPage): array
    {
        [$where, $p] = self::where($f);
        $total = (int) Database::value('SELECT COUNT(*) ' . self::FROM . " $where", $p);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $rows = Database::all(
            self::SELECT . ' ' . self::FROM . " $where
             ORDER BY i.purchase_date DESC, i.id DESC LIMIT $perPage OFFSET $offset",
            $p
        );
        return compact('rows', 'total', 'page', 'pages');
    }

    public static function forAsset(int $assetId): array
    {
        return Database::all(
            self::SELECT . ' ' . self::FROM . ' WHERE a.id = :id AND i.user_id = :u ORDER BY i.purchase_date DESC, i.id DESC',
            ['id' => $assetId, 'u' => Auth::id()]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT * FROM investments WHERE id = :id AND user_id = :u',
            ['id' => $id, 'u' => Auth::id()]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO investments (user_id, asset_id, operation_type, amount_eur, purchase_date, commission_eur, notes)
             VALUES (:user_id, :asset_id, :operation_type, :amount_eur, :purchase_date, :commission_eur, :notes)',
            self::bind($d) + ['user_id' => Auth::id(), 'operation_type' => $d['operation_type']]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE investments SET asset_id = :asset_id, amount_eur = :amount_eur,
                    purchase_date = :purchase_date, commission_eur = :commission_eur, notes = :notes
             WHERE id = :id AND user_id = :user_id',
            self::bind($d) + ['id' => $id, 'user_id' => Auth::id()]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM investments WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => Auth::id()]);
    }

    private static function bind(array $d): array
    {
        return [
            'asset_id'       => $d['asset_id'],
            'amount_eur'     => $d['amount_eur'],
            'purchase_date'  => $d['purchase_date'],
            'commission_eur' => $d['commission_eur'],
            'notes'          => $d['notes'],
        ];
    }
}
