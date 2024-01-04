<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Mon, 05 Sept 2022 01:31:39 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;

use App\Actions\Inventory\Warehouse\StoreWarehouse;
use App\Actions\Inventory\Warehouse\UpdateWarehouse;
use App\Models\Inventory\Warehouse;
use App\Services\Organisation\SourceOrganisationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;

class FetchWarehouses extends FetchAction
{
    public string $commandSignature = 'fetch:warehouses {organisations?*} {--s|source_id=} {--d|db_suffix=}';

    #[NoReturn] public function handle(SourceOrganisationService $organisationSource, int $organisationSourceId): ?Warehouse
    {
        if ($warehouseData = $organisationSource->fetchWarehouse($organisationSourceId)) {
            if ($warehouse = Warehouse::where('source_id', $warehouseData['warehouse']['source_id'])
                ->first()) {
                $warehouse = UpdateWarehouse::run(
                    warehouse: $warehouse,
                    modelData: $warehouseData['warehouse']
                );
            } else {
                $warehouse = StoreWarehouse::run(
                    organisation: $organisationSource->getOrganisation(),
                    modelData:    $warehouseData['warehouse'],
                );
            }


            return $warehouse;
        }

        return null;
    }

    public function getModelsQuery(): Builder
    {
        return DB::connection('aurora')
            ->table('Warehouse Dimension')
            ->select('Warehouse Key as source_id')
            ->orderBy('source_id');
    }

    public function count(): ?int
    {
        return DB::connection('aurora')->table('Warehouse Dimension')->count();
    }
}
