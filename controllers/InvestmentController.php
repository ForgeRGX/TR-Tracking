<?php
declare(strict_types=1);

/** Compras ('buy') y ventas ('sell'): comparten tabla, formulario y validaciones. */
final class InvestmentController extends BaseController
{
    public function index(): void
    {
        $f = Investment::filters($_GET);
        $res = Investment::paginate($f, (int) ($_GET['page'] ?? 1), (int) config('per_page'));
        $this->render('investments/index', $res + [
            'title'        => t('Inversiones'),
            'filters'      => $f,
            'types'        => AssetType::all(),
            'assetOptions' => Asset::options(),
            'totals'       => Stats::totals($f),
            'query'        => Investment::queryParams($f),
        ]);
    }

    /** /investments/create se mantiene como alias de "comprar". */
    public function create(): void
    {
        $this->createForm('buy');
    }

    public function buy(): void
    {
        $this->createForm('buy');
    }

    public function sell(): void
    {
        $this->createForm('sell');
    }

    public function store(): void
    {
        $input = $this->readInput();
        $op = $input['operation_type'];
        if (!in_array($op, ['buy', 'sell'], true)) {
            $this->notFound();
        }
        [$errors, $clean] = $this->validate($input, $op);
        if ($errors) {
            $this->form($input, $errors, null, 422);
            return;
        }
        Investment::create($clean);
        flash('success', t($op === 'sell' ? 'Venta registrada correctamente.' : 'Compra registrada correctamente.'));
        redirect('/investments');
    }

    public function edit(string $id): void
    {
        $row = Investment::find((int) $id) ?? $this->notFound();
        $values = $this->formValues($row);
        $values['amount_eur'] = form_decimal($values['amount_eur']);
        $values['commission_eur'] = form_decimal($values['commission_eur']);
        $this->form($values, [], (int) $id);
    }

    public function update(string $id): void
    {
        $row = Investment::find((int) $id) ?? $this->notFound();
        $op = (string) $row['operation_type']; // el tipo de operación no se puede cambiar al editar
        $input = $this->readInput();
        $input['operation_type'] = $op;
        [$errors, $clean] = $this->validate($input, $op);
        if ($errors) {
            $this->form($input, $errors, (int) $id, 422);
            return;
        }
        Investment::update((int) $id, $clean);
        flash('success', t($op === 'sell' ? 'Venta actualizada correctamente.' : 'Compra actualizada correctamente.'));
        redirect('/investments');
    }

    public function delete(string $id): void
    {
        $row = Investment::find((int) $id) ?? $this->notFound();
        Investment::delete((int) $id);
        flash('success', t($row['operation_type'] === 'sell' ? 'Venta eliminada.' : 'Compra eliminada.'));
        redirect('/investments');
    }

    private function createForm(string $op): void
    {
        $assetId = filter_var($_GET['asset_id'] ?? null, FILTER_VALIDATE_INT);
        $this->form([
            'operation_type' => $op,
            'asset_id'       => $assetId !== false && Asset::find($assetId) ? (string) $assetId : '',
            'amount_eur'     => '',
            'purchase_date'  => date('Y-m-d'),
            'commission_eur' => form_decimal('0.00'),
            'notes'          => '',
        ], [], null);
    }

    private function form(array $item, array $errors, ?int $id, int $status = 200): void
    {
        $sell = ($item['operation_type'] ?? 'buy') === 'sell';
        $title = $id
            ? ($sell ? 'Editar venta' : 'Editar compra')
            : ($sell ? 'Vender' : 'Comprar');
        $this->render('investments/form', [
            'title'        => t($title),
            'item'         => $item,
            'errors'       => $errors,
            'id'           => $id,
            'operation'    => $sell ? 'sell' : 'buy',
            'assetOptions' => Asset::options(),
        ], $status);
    }

    private function readInput(): array
    {
        return [
            'operation_type' => $this->post('operation_type'),
            'asset_id'       => $this->post('asset_id'),
            'amount_eur'     => $this->post('amount_eur'),
            'purchase_date'  => $this->post('purchase_date'),
            'commission_eur' => $this->post('commission_eur'),
            'notes'          => $this->post('notes'),
        ];
    }

    /** @return array{0:array,1:array} [errores, datos limpios] */
    private function validate(array $in, string $op): array
    {
        $errors = [];
        $clean = ['operation_type' => $op];

        $assetId = filter_var($in['asset_id'], FILTER_VALIDATE_INT);
        if ($assetId === false || Asset::find($assetId) === null) {
            $errors['asset_id'] = t('Selecciona un activo válido.');
        } else {
            $clean['asset_id'] = $assetId;
        }

        if ($in['amount_eur'] === '') {
            $errors['amount_eur'] = t('El importe es obligatorio.');
        } else {
            $amount = parse_money($in['amount_eur']);
            if ($amount === null || (float) $amount <= 0) {
                $errors['amount_eur'] = t('El importe debe ser un número mayor que 0, con máximo 2 decimales.');
            } else {
                $clean['amount_eur'] = $amount;
            }
        }

        if ($in['commission_eur'] === '') {
            $clean['commission_eur'] = '0.00';
        } else {
            $commission = parse_money($in['commission_eur']);
            if ($commission === null) {
                $errors['commission_eur'] = t('La comisión debe ser un número igual o mayor que 0, con máximo 2 decimales.');
            } else {
                $clean['commission_eur'] = $commission;
            }
        }
        // Una venta no puede costar más de lo que ingresa (la ganancia sería negativa)
        if ($op === 'sell' && isset($clean['amount_eur'], $clean['commission_eur'])
            && (float) $clean['commission_eur'] > (float) $clean['amount_eur']) {
            $errors['commission_eur'] = t('La comisión no puede ser mayor que el importe de la venta.');
        }

        $date = parse_date($in['purchase_date']);
        if ($date === null) {
            $errors['purchase_date'] = t($op === 'sell' ? 'Introduce una fecha de venta válida.' : 'Introduce una fecha de compra válida.');
        } else {
            $clean['purchase_date'] = $date;
        }

        if (mb_strlen($in['notes']) > 1000) {
            $errors['notes'] = t('Las notas no pueden superar los 1000 caracteres.');
        }
        $clean['notes'] = $in['notes'] === '' ? null : $in['notes'];

        return [$errors, $clean];
    }
}
