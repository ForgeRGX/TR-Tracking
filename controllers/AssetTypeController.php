<?php
declare(strict_types=1);

final class AssetTypeController extends BaseController
{
    public function index(): void
    {
        $this->render('types/index', ['title' => t('Tipos de activo'), 'types' => AssetType::all()]);
    }

    public function create(): void
    {
        $this->form(['name' => '', 'description' => ''], [], null);
    }

    public function store(): void
    {
        $input = $this->readInput();
        $errors = $this->validate($input, null);
        if ($errors) {
            $this->form($input, $errors, null, 422);
            return;
        }
        try {
            AssetType::create($input['name'], $input['description'] ?: null);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe un tipo con ese nombre.')], null, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Tipo de activo creado correctamente.'));
        redirect('/types');
    }

    public function edit(string $id): void
    {
        $row = AssetType::find((int) $id) ?? $this->notFound();
        $this->form($this->formValues($row), [], (int) $id);
    }

    public function update(string $id): void
    {
        AssetType::find((int) $id) ?? $this->notFound();
        $input = $this->readInput();
        $errors = $this->validate($input, (int) $id);
        if ($errors) {
            $this->form($input, $errors, (int) $id, 422);
            return;
        }
        try {
            AssetType::update((int) $id, $input['name'], $input['description'] ?: null);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                $this->form($input, ['name' => t('Ya existe un tipo con ese nombre.')], (int) $id, 422);
                return;
            }
            throw $e;
        }
        flash('success', t('Tipo de activo actualizado correctamente.'));
        redirect('/types');
    }

    public function delete(string $id): void
    {
        $type = AssetType::find((int) $id) ?? $this->notFound();
        $count = AssetType::assetsCount((int) $id);
        if ($count > 0) {
            flash('danger', t('No se puede eliminar el tipo «%s»: tiene %d activo(s) asociado(s). Elimina o reasigna esos activos primero.', [$type['name'], $count]));
            redirect('/types');
        }
        try {
            AssetType::delete((int) $id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash('danger', t('No se puede eliminar el tipo porque tiene datos asociados.'));
                redirect('/types');
            }
            throw $e;
        }
        flash('success', t('Tipo de activo eliminado.'));
        redirect('/types');
    }

    private function form(array $item, array $errors, ?int $id, int $status = 200): void
    {
        $this->render('types/form', [
            'title'  => $id ? t('Editar tipo de activo') : t('Nuevo tipo de activo'),
            'item'   => $item,
            'errors' => $errors,
            'id'     => $id,
        ], $status);
    }

    private function readInput(): array
    {
        return [
            'name'        => preg_replace('/\s+/u', ' ', $this->post('name')) ?? '',
            'description' => $this->post('description'),
        ];
    }

    private function validate(array $in, ?int $exceptId): array
    {
        $errors = [];
        if ($in['name'] === '') {
            $errors['name'] = t('El nombre es obligatorio.');
        } elseif (mb_strlen($in['name']) > 100) {
            $errors['name'] = t('El nombre no puede superar los 100 caracteres.');
        } elseif (AssetType::nameExists($in['name'], $exceptId)) {
            $errors['name'] = t('Ya existe un tipo con ese nombre.');
        }
        if (mb_strlen($in['description']) > 255) {
            $errors['description'] = t('La descripción no puede superar los 255 caracteres.');
        }
        return $errors;
    }

    private function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }
}
