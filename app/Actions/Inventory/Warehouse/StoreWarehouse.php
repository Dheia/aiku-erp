<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Tue, 30 Aug 2022 12:25:15 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia F
 */

namespace App\Actions\Inventory\Warehouse;

use App\Actions\OrgAction;
use App\Actions\Inventory\Warehouse\Hydrators\WarehouseHydrateUniversalSearch;
use App\Actions\SysAdmin\Organisation\Hydrators\OrganisationHydrateWarehouse;
use App\Actions\SysAdmin\User\UserAddRoles;
use App\Enums\SysAdmin\Authorisation\RolesEnum;
use App\Models\SysAdmin\Organisation;
use App\Models\Inventory\Warehouse;
use App\Models\SysAdmin\Role;
use App\Rules\IUnique;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Lorisleiva\Actions\ActionRequest;

class StoreWarehouse extends OrgAction
{
    private bool $asAction = false;


    public function handle(Organisation $organisation, $modelData): Warehouse
    {
        data_set($modelData, 'group_id', $organisation->group_id);
        /** @var Warehouse $warehouse */
        $warehouse = $organisation->warehouses()->create($modelData);
        $warehouse->stats()->create();

        SeedWarehousePermissions::run($warehouse);

        $orgAdmins = $organisation->group->users()->with('roles')->get()->filter(
            fn ($user) => $user->roles->where('name', "org-admin-$organisation->slug")->toArray()
        );

        foreach ($orgAdmins as $orgAdmin) {
            UserAddRoles::run($orgAdmin, [
                Role::where('name', RolesEnum::getRoleName('warehouse-admin', $warehouse))->first()
            ]);
        }

        OrganisationHydrateWarehouse::run($organisation);
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
            'code' => ['required','between:2,4', 'alpha_dash',
                       new IUnique(
                           table: 'warehouses',
                           extraConditions: [
                               ['column' => 'group_id', 'value' => $this->organisation->group_id],
                           ]
                       ),
                ],
            'name'     => ['required', 'max:250', 'string'],
            'source_id'=> ['sometimes','string'],
        ];
    }

    public function action(Organisation $organisation, array $modelData): Warehouse
    {
        $this->asAction = true;
        $this->initialisation($organisation, $modelData);

        return $this->handle($organisation, $this->validatedData);
    }


    public function asController(Organisation $organisation, ActionRequest $request): Warehouse
    {
        $this->initialisation($organisation, $request);

        return $this->handle($organisation, $this->validatedData);
    }


    public function htmlResponse(Warehouse $warehouse): RedirectResponse
    {
        return Redirect::route('grp.org.inventory.warehouses.index');
    }

    public string $commandSignature = 'warehouse:create {organisation : organisation slug} {code} {name}';

    public function asCommand(Command $command): int
    {
        $this->asAction = true;

        try {
            $organisation = Organisation::where('slug', $command->argument('organisation'))->firstOrFail();
        } catch (Exception $e) {
            $command->error($e->getMessage());

            return 1;
        }
        $this->organisation = $organisation;
        setPermissionsTeamId($organisation->group->id);

        $this->setRawAttributes([
            'code'        => $command->argument('code'),
            'name'        => $command->argument('name'),
        ]);

        try {
            $validatedData = $this->validateAttributes();
        } catch (Exception $e) {
            $command->error($e->getMessage());
            return 1;
        }

        $shop = $this->handle($organisation, $validatedData);

        $command->info("Warehouse $shop->code created successfully 🎉");

        return 0;
    }

}
