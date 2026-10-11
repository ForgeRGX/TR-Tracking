<?php
declare(strict_types=1);

final class AssetController extends BaseController
{
    public function index(): void
    {
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
        $typeId = filter_var($_GET['type_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $typeId = $typeId === false ? null : $typeId;

        $this->render('assets/index', [
            'title'  => t('Activos'),
            'assets' => Asset::search($q, $typeId),
            'types'  => AssetType::all(),
            'q'      => $q,
            'typeId' => $typeId,
        ]);
    }

    public function show(string $id): void
    {
        $asset = Asset::find((int) $id) ?? $this->notFound();
        $this->render('assets/show', [
            'title'  => $asset['name'],
            'asset'  => $asset,
            'totals' => Stats::totals(Investment::filters(['asset_id' => (int) $id])),
            'rows'   => Investment::forAsset((int) $id),
        ]);
    }

    public function create(): void
    {
        $typeId = filter_var($_GET['type_id'] ?? null, FILTER_VALIDATE_INT);
        $this->form([
            'asset_type_id' => $typeId === false ? '' : (string) $typeId,
            'name' => '', 'ticker' => '', 'isin' => '', 'currency' => 'EUR', 'description' => '',
        ], [], null);
    }

    public function store(): void
    {
        $input = $this->readInput();
        [$errors, $clean] = $this->validate($input, null);
        if ($errors) {
            $this->form($input, $errors, null, 422);
            return;
        }
        try {
            Asset::create($clean);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe un activo con ese nombre en este tipo.')], null, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Activo creado correctamente.'));
        redirect('/assets');
    }

    public function edit(string $id): void
    {
        $row = Asset::find((int) $id) ?? $this->notFound();
        $this->form($this->formValues($row), [], (int) $id);
    }

    public function update(string $id): void
    {
        Asset::find((int) $id) ?? $this->notFound();
        $input = $this->readInput();
        [$errors, $clean] = $this->validate($input, (int) $id);
        if ($errors) {
            $this->form($input, $errors, (int) $id, 422);
            return;
        }
        try {
            Asset::update((int) $id, $clean);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe un activo con ese nombre en este tipo.')], (int) $id, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Activo actualizado correctamente.'));
        redirect('/assets');
    }

    public function delete(string $id): void
    {
        $asset = Asset::find((int) $id) ?? $this->notFound();
        $count = Asset::investmentsCount((int) $id);
        if ($count > 0) {
            flash('danger', t('No se puede eliminar «%s»: tiene %d inversión(es) asociada(s). Elimínalas o edítalas antes.', [$asset['name'], $count]));
            redirect('/assets');
        }
        try {
            Asset::delete((int) $id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash('danger', t('No se puede eliminar el activo porque tiene datos asociados.'));
                redirect('/assets');
            }
            throw $e;
        }
        flash('success', t('Activo eliminado.'));
        redirect('/assets');
    }

    private function form(array $item, array $errors, ?int $id, int $status = 200): void
    {
        $this->render('assets/form', [
            'title'  => $id ? t('Editar activo') : t('Nuevo activo'),
            'item'   => $item,
            'errors' => $errors,
            'id'     => $id,
            'types'  => AssetType::all(),
        ], $status);
    }

    private function readInput(): array
    {
        return [
            'asset_type_id' => $this->post('asset_type_id'),
            'name'          => preg_replace('/\s+/u', ' ', $this->post('name')) ?? '',
            'ticker'        => $this->post('ticker'),
            'isin'          => $this->post('isin'),
            'currency'      => $this->post('currency'),
            'description'   => $this->post('description'),
        ];
    }

    /** @return array{0:array,1:array} [errores, datos limpios] */
    private function validate(array $in, ?int $exceptId): array
    {
        $errors = [];
        $clean = [];

        $typeId = filter_var($in['asset_type_id'], FILTER_VALIDATE_INT);
        if ($typeId === false || AssetType::find($typeId) === null) {
            $errors['asset_type_id'] = t('Selecciona un tipo de activo.');
        } else {
            $clean['asset_type_id'] = $typeId;
        }

        if ($in['name'] === '') {
            $errors['name'] = t('El nombre es obligatorio.');
        } elseif (mb_strlen($in['name']) > 150) {
            $errors['name'] = t('El nombre no puede superar los 150 caracteres.');
        } elseif (isset($clean['asset_type_id']) && Asset::existsInType($typeId, $in['name'], $exceptId)) {
            $errors['name'] = t('Ya existe un activo con ese nombre en este tipo.');
        }
        $clean['name'] = $in['name'];

        $ticker = strtoupper($in['ticker']);
        if ($ticker !== '' && !preg_match('/^[A-Z0-9][A-Z0-9.\-_:]{0,19}$/', $ticker)) {
            $errors['ticker'] = t('Ticker no válido (máx. 20 caracteres: letras, números y . - _ :).');
        }
        $clean['ticker'] = $ticker === '' ? null : $ticker;

        $isin = strtoupper($in['isin']);
        if ($isin !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)) {
            $errors['isin'] = t('El ISIN debe tener 12 caracteres (2 letras, 9 alfanuméricos y 1 dígito).');
        }
        $clean['isin'] = $isin === '' ? null : $isin;

        $currency = strtoupper($in['currency'] === '' ? 'EUR' : $in['currency']);
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            $errors['currency'] = t('La moneda debe ser un código de 3 letras (p. ej. EUR, USD).');
        }
        $clean['currency'] = $currency;

        if (mb_strlen($in['description']) > 1000) {
            $errors['description'] = t('La descripción no puede superar los 1000 caracteres.');
        }
        $clean['description'] = $in['description'] === '' ? null : $in['description'];

        return [$errors, $clean];
    }

    private function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }
}
