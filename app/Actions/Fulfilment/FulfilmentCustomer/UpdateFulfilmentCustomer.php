<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 26 Feb 2024 21:27:03 Central Standard Time, Mexico City, Mexico
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\FulfilmentCustomer;

use App\Actions\CRM\Customer\UpdateCustomer;
use App\Actions\OrgAction;
use App\Actions\Traits\WithActionUpdate;
use App\Models\Fulfilment\Fulfilment;
use App\Models\Fulfilment\FulfilmentCustomer;
use App\Models\Market\Shop;
use App\Models\SysAdmin\Organisation;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\ActionRequest;

class UpdateFulfilmentCustomer extends OrgAction
{
    use WithActionUpdate;


    public function handle(FulfilmentCustomer $fulfilmentCustomer, array $modelData): FulfilmentCustomer
    {

        if(Arr::exists($modelData, 'contact_name')
            or Arr::exists($modelData, 'company_name')
            or Arr::exists($modelData, 'phone')) {
            UpdateCustomer::run($fulfilmentCustomer->customer, $modelData);

            return $fulfilmentCustomer;
        }

        return $this->update($fulfilmentCustomer, $modelData, ['data']);
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("fulfilments.{$this->fulfilment->id}.edit");
    }

    public function rules(): array
    {
        return [
            'contact_name'    => ['sometimes', 'string'],
            'company_name'    => ['sometimes', 'string'],
            'phone'           => ['sometimes', 'string'],
            'pallets_storage' => ['sometimes', 'boolean'],
            'items_storage'   => ['sometimes', 'boolean'],
            'dropshipping'    => ['sometimes', 'boolean'],
        ];
    }


    public function asController(Organisation $organisation, Shop $shop, Fulfilment $fulfilment, FulfilmentCustomer $fulfilmentCustomer, ActionRequest $request): FulfilmentCustomer
    {
        $this->initialisationFromFulfilment($fulfilmentCustomer->fulfilment, $request);

        return $this->handle($fulfilmentCustomer, $this->validatedData);
    }

    public function action(Organisation $organisation, Shop $shop, Fulfilment $fulfilment, FulfilmentCustomer $fulfilmentCustomer, array $modelData): FulfilmentCustomer
    {
        $this->asAction = true;
        $this->initialisationFromFulfilment($fulfilmentCustomer->fulfilment, $modelData);

        return $this->handle($fulfilmentCustomer, $this->validatedData);
    }


}
