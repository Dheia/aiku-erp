<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 24 Jan 2024 11:06:14 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\Fulfilment;

use App\Actions\Market\Shop\StoreShop;
use App\Actions\OrgAction;
use App\Actions\SysAdmin\User\UserAddRoles;
use App\Actions\Traits\Rules\WithShopRules;
use App\Enums\SysAdmin\Authorisation\RolesEnum;
use App\Models\Fulfilment\Fulfilment;
use App\Models\Market\Shop;
use App\Models\SysAdmin\Organisation;
use App\Models\SysAdmin\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Lorisleiva\Actions\ActionRequest;

class StoreFulfilment extends OrgAction
{
    use WithShopRules;
    public function handle(Shop $shop, $modelData): Fulfilment
    {
        data_set($modelData, 'group_id', $shop->group_id);
        data_set($modelData, 'organisation_id', $shop->organisation_id);


        /** @var Fulfilment $fulfilment */
        $fulfilment = $shop->fulfilment()->create($modelData);
        $fulfilment->stats()->create();

        SeedFulfilmentPermissions::run($fulfilment);

        $orgAdmins = $fulfilment->group->users()->with('roles')->get()->filter(
            fn ($user) => $user->roles->where('name', "org-admin-{$fulfilment->organisation->id}")->toArray()
        );

        foreach ($orgAdmins as $orgAdmin) {
            UserAddRoles::run($orgAdmin, [
                Role::where('name', RolesEnum::getRoleName(RolesEnum::FULFILMENT_ADMIN->value, $fulfilment))->first()
            ]);
        }

        return $fulfilment;
    }


    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("fulfilments.{$this->organisation->id}.edit");
    }

    public function rules(ActionRequest $request): array
    {
        return $this->getStoreShopRules();
    }


    public function asController(Organisation $organisation, ActionRequest $request): Fulfilment
    {
        $this->initialisation($organisation, $request);
        $shop=StoreShop::make()->action($organisation, $this->validatedData);
        return $shop->fulfilment;
    }


    public function action(Shop $shop, $modelData, $hydratorDelay = 0): Fulfilment
    {
        $this->asAction       = true;
        $this->hydratorsDelay = $hydratorDelay;
        $this->initialisation($shop->organisation, $modelData);

        return $this->handle($shop, $this->validatedData);
    }

    public function htmlResponse(Fulfilment $fulfilment): RedirectResponse
    {
        return Redirect::route(
            'grp.org.fulfilment.show',
            [
                $this->organisation->slug,
                $fulfilment->slug
            ]
        );
    }

}
