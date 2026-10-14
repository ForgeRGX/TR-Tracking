<?php
declare(strict_types=1);

final class ScheduledPurchaseController extends BaseController
{
    /** Lista de tareas + ocurrencias pendientes. Genera ocurrencias nuevas si corresponde. */
    public function index(): void
    {
        ScheduledPurchase::generatePendingOccurrences();
        $this->render('scheduled/index', [
            'title'    => t('Tareas programadas'),
            'list'     => ScheduledPurchase::all(),
            'pending'  => ScheduledPurchaseOccurrence::pending(),
        ]);
    }

    public function create(): void
    {
        $this->form($this->defaults(), [], null);
    }

    public function store(): void
    {
        $input = $this->readInput();
        [$errors, $clean] = $this->validate($input);
        if ($errors) {
            $this->form($input, $errors, null, 422);
            return;
        }
        ScheduledPurchase::create($clean);
        flash('success', t('Tarea programada creada correctamente.'));
        redirect('/scheduled');
    }

    public function show(string $id): void
    {
        $sp  = ScheduledPurchase::find((int) $id) ?? $this->notFound();
        $occ = ScheduledPurchaseOccurrence::forSchedule((int) $id);
        $stats = ScheduledPurchaseOccurrence::statsForSchedule((int) $id);
        $this->render('scheduled/show', [
            'title'  => $sp['asset_name'] . ' — ' . frequency_label($sp['frequency']),
            'sp'     => $sp,
            'occ'    => $occ,
            'stats'  => $stats,
        ]);
    }

    public function edit(string $id): void
    {
        $sp = ScheduledPurchase::find((int) $id) ?? $this->notFound();
        $values = $this->formValues($sp);
        $values['amount_eur']     = form_decimal($values['amount_eur']);
        $values['commission_eur'] = form_decimal($values['commission_eur']);
        $this->form($values, [], (int) $id);
    }

    public function update(string $id): void
    {
        ScheduledPurchase::find((int) $id) ?? $this->notFound();
        $input = $this->readInput();
        [$errors, $clean] = $this->validate($input);
        if ($errors) {
            $this->form($input, $errors, (int) $id, 422);
            return;
        }
        ScheduledPurchase::update((int) $id, $clean);
        flash('success', t('Tarea programada actualizada correctamente.'));
        redirect('/scheduled');
    }

    public function toggle(string $id): void
    {
        $sp = ScheduledPurchase::find((int) $id) ?? $this->notFound();
        ScheduledPurchase::toggleActive((int) $id);
        $msg = $sp['is_active'] ? t('Tarea desactivada.') : t('Tarea activada.');
        flash('success', $msg);
        redirect('/scheduled');
    }

    public function delete(string $id): void
    {
        ScheduledPurchase::find((int) $id) ?? $this->notFound();
        ScheduledPurchase::delete((int) $id);
        flash('success', t('Tarea programada eliminada.'));
        redirect('/scheduled');
    }

    /** Confirma una ocurrencia: crea la inversión y redirige. */
    public function confirmOccurrence(string $id): void
    {
        $occ = ScheduledPurchaseOccurrence::find((int) $id) ?? $this->notFound();
        $date = parse_date($this->post('confirmed_date'));
        if ($date === null) {
            flash('danger', t('Introduce una fecha de compra válida.'));
            redirect('/scheduled');
        }
        ScheduledPurchaseOccurrence::confirm((int) $id, $date);
        flash('success', t('Compra confirmada y registrada correctamente.'));
        redirect('/scheduled');
    }

    /** Omite una ocurrencia sin crear inversión. */
    public function skipOccurrence(string $id): void
    {
        ScheduledPurchaseOccurrence::find((int) $id) ?? $this->notFound();
        ScheduledPurchaseOccurrence::skip((int) $id);
        flash('success', t('Ocurrencia omitida.'));
        redirect('/scheduled');
    }

    /* ---------------------------------------------------------------------- */

    private function defaults(): array
    {
        return [
            'asset_id'       => '',
            'frequency'      => 'monthly_start',
            'amount_eur'     => '',
            'commission_eur' => form_decimal('0.00'),
            'start_date'     => date('Y-m-01'),
            'end_date'       => '',
            'notes'          => '',
        ];
    }

    private function form(array $item, array $errors, ?int $id, int $status = 200): void
    {
        $this->render('scheduled/form', [
            'title'        => $id ? t('Editar tarea programada') : t('Nueva tarea programada'),
            'item'         => $item,
            'errors'       => $errors,
            'id'           => $id,
            'assetOptions' => Asset::options(),
            'frequencies'  => ScheduledPurchase::FREQUENCIES,
        ], $status);
    }

    private function readInput(): array
    {
        return [
            'asset_id'       => $this->post('asset_id'),
            'frequency'      => $this->post('frequency'),
            'amount_eur'     => $this->post('amount_eur'),
            'commission_eur' => $this->post('commission_eur'),
            'start_date'     => $this->post('start_date'),
            'end_date'       => $this->post('end_date'),
            'notes'          => $this->post('notes'),
        ];
    }

    /** @return array{0:array,1:array} [errores, datos limpios] */
    private function validate(array $in): array
    {
        $errors = [];
        $clean  = [];

        $assetId = filter_var($in['asset_id'], FILTER_VALIDATE_INT);
        if ($assetId === false || Asset::find($assetId) === null) {
            $errors['asset_id'] = t('Selecciona un activo válido.');
        } else {
            $clean['asset_id'] = $assetId;
        }

        if (!in_array($in['frequency'], ScheduledPurchase::FREQUENCIES, true)) {
            $errors['frequency'] = t('Selecciona una frecuencia válida.');
        } else {
            $clean['frequency'] = $in['frequency'];
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

        $startDate = parse_date($in['start_date']);
        if ($startDate === null) {
            $errors['start_date'] = t('Introduce una fecha de inicio válida.');
        } else {
            $clean['start_date'] = $startDate;
        }

        if ($in['end_date'] !== '') {
            $endDate = parse_date($in['end_date']);
            if ($endDate === null) {
                $errors['end_date'] = t('Introduce una fecha de fin válida.');
            } elseif (isset($clean['start_date']) && $endDate < $clean['start_date']) {
                $errors['end_date'] = t('La fecha de fin no puede ser anterior a la de inicio.');
            } else {
                $clean['end_date'] = $endDate;
            }
        } else {
            $clean['end_date'] = null;
        }

        if (mb_strlen($in['notes']) > 1000) {
            $errors['notes'] = t('Las notas no pueden superar los 1000 caracteres.');
        }
        $clean['notes'] = $in['notes'] === '' ? null : $in['notes'];

        return [$errors, $clean];
    }
}
