<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Tue, 25 Oct 2022 11:01:21 British Summer Time, Sheffield, UK
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Actions\Procurement\Supplier;

use App\Actions\Helpers\Address\UpdateAddress;
use App\Actions\GrpAction;
use App\Actions\Procurement\Supplier\Hydrators\SupplierHydrateUniversalSearch;
use App\Actions\Traits\WithActionUpdate;
use App\Http\Resources\Procurement\SupplierResource;
use App\Models\Procurement\Supplier;
use App\Rules\IUnique;
use App\Rules\ValidAddress;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\ActionRequest;

class UpdateSupplier extends GrpAction
{
    use WithActionUpdate;

    private Supplier $supplier;
    private bool $action = false;

    public function handle(Supplier $supplier, array $modelData): Supplier
    {
        $addressData = Arr::get($modelData, 'address');
        Arr::forget($modelData, 'address');
        $supplier = $this->update($supplier, $modelData, ['data', 'settings']);

        if ($addressData) {
            UpdateAddress::run($supplier->getAddress('contact'), $addressData);
            $supplier->location = $supplier->getLocation();
            $supplier->save();
        }

        SupplierHydrateUniversalSearch::dispatch($supplier);

        return $supplier;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo("procurement.".$this->group->id.".edit");
    }

    public function rules(): array
    {
        return [
            'code'         => [
                'sometimes',
                'required',
                'max:9',
                'alpha_dash',
                new IUnique(
                    table: 'agents',
                    extraConditions: [
                        ['column' => 'group_id', 'value' => $this->group->id],
                        [
                            'column'   => 'id',
                            'operator' => '!=',
                            'value'    => $this->supplier->id
                        ],
                    ]
                ),
            ],
            'contact_name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email'        => ['sometimes', 'nullable', 'email'],
            'phone'        => ['sometimes', 'nullable', 'phone:AUTO'],
            'address'      => ['sometimes', 'required', new ValidAddress()],
            'currency_id'  => ['sometimes', 'required', 'exists:currencies,id'],
        ];
    }

    public function action(Supplier $supplier, $modelData): Supplier
    {
        $this->supplier = $supplier;
        $this->action   = true;
        $this->initialisation($supplier->group, $modelData);

        return $this->handle($supplier, $this->validatedData);
    }

    public function asController(Supplier $supplier, ActionRequest $request): Supplier
    {
        $this->supplier = $supplier;
        $this->initialisation($supplier->group, $request);

        return $this->handle($supplier, $this->validatedData);
    }


    public function jsonResponse(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }
}
