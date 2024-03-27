<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 04 Dec 2023 16:14:39 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SysAdmin\Group;

use App\Actions\HydrateModel;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateGuests;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateInventory;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateInvoices;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateJobPositions;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateOrganisations;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydratePaymentAccounts;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydratePayments;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydratePaymentServiceProviders;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateSupplyChain;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateTradeUnits;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateUsers;
use App\Actions\Traits\WithNormalise;
use App\Models\SysAdmin\Group;
use Exception;
use Illuminate\Console\Command;

class HydrateGroup extends HydrateModel
{
    use WithNormalise;

    public string $commandSignature = 'group:hydrate {group : Group slug}';


    public function handle(Group $group): void
    {
        GroupHydrateGuests::run($group);
        GroupHydrateJobPositions::run($group);
        GroupHydrateOrganisations::run($group);
        GroupHydrateSupplyChain::run($group);
        GroupHydrateInventory::run($group);
        GroupHydrateTradeUnits::run($group);
        GroupHydrateUsers::run($group);
        GroupHydrateInvoices::run($group);
        GroupHydratePayments::run($group);
        GroupHydratePaymentAccounts::run($group);
        GroupHydratePaymentServiceProviders::run($group);

    }


    public function asCommand(Command $command): int
    {
        try {
            $group = Group::where('slug', $command->argument('group'))->firstorFail();
        } catch (Exception $e) {
            $command->error($e->getMessage());
            return 1;
        }


        $this->handle($group);

        return 0;
    }
}
