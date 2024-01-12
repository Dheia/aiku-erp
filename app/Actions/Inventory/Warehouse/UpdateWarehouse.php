<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Mon, 04 Oct 2021 11:48:37 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2021, Inikoo
 *  Version 4.0
 */

namespace App\Actions\Inventory\Warehouse;

use App\Actions\OrgAction;
use App\Actions\Inventory\Warehouse\Hydrators\WarehouseHydrateUniversalSearch;
use App\Actions\Traits\WithActionUpdate;
use App\Enums\Inventory\Warehouse\WarehouseStateEnum;
use App\Http\Resources\Inventory\WarehouseResource;
use App\Models\Inventory\Warehouse;
use App\Rules\IUnique;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;

class UpdateWarehouse extends OrgAction
{
    use WithActionUpdate;

    private bool $asAction = false;


    public function handle(Warehouse $warehouse, array $modelData): Warehouse
    {
        $warehouse = $this->update($warehouse, $modelData, ['data', 'settings']);
        WarehouseHydrateUniversalSearch::dispatch($warehouse);

        return $warehouse;
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("inventory.warehouses.edit");
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'required', 'max:16', 'alpha_dash',
                       new IUnique(
                           table: 'warehouses',
                           extraConditions: [
                               ['column' => 'group_id', 'value' => $this->organisation->group_id],
                               ['column' => 'id', 'value' => $this->warehouse->id, 'operation' => '!=']
                           ]
                       ),],
            'name'      => ['sometimes', 'required', 'max:250', 'string'],
            'state'     => ['sometimes', Rule::enum(WarehouseStateEnum::class)],

        ];
    }


    public function asController(Warehouse $warehouse, ActionRequest $request): Warehouse
    {
        $this->warehouse = $warehouse;
        $this->initialisation($warehouse->organisation, $request);


        return $this->handle(
            warehouse: $warehouse,
            modelData: $this->validatedData
        );
    }

    public function action(Warehouse $warehouse, $modelData): Warehouse
    {
        $this->asAction  = true;
        $this->warehouse = $warehouse;
        $this->initialisation($warehouse->organisation, $modelData);


        return $this->handle(
            warehouse: $warehouse,
            modelData: $this->validatedData
        );
    }

    public function jsonResponse(Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }
}
