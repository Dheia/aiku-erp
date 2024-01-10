<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 03 Jan 2024 21:08:49 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\SysAdmin\Organisation;

use App\Actions\Traits\WithEnumStats;
use App\Enums\HumanResources\ClockingMachine\ClockingMachineTypeEnum;
use App\Models\HumanResources\ClockingMachine;
use App\Models\SysAdmin\Organisation;
use Lorisleiva\Actions\Concerns\AsAction;

class OrganisationHydrateClockingMachines
{
    use AsAction;
    use WithEnumStats;

    public function handle(Organisation $organisation): void
    {
        $stats = [
            'number_clocking_machines' => $organisation->clockingMachines()->count()
        ];
        $stats = array_merge(
            $stats,
            $this->getEnumStats(
                model: 'clocking_machines',
                field: 'type',
                enum: ClockingMachineTypeEnum::class,
                models: ClockingMachine::class,
                where: function ($q) use ($organisation) {
                    $q->where('organisation_id', $organisation->id);
                }
            )
        );

        $organisation->humanResourcesStats()->update($stats);
    }
}
