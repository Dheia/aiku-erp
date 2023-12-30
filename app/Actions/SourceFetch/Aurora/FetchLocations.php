<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Mon, 05 Sept 2022 01:11:27 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;

use App\Actions\Inventory\Location\StoreLocation;
use App\Actions\Inventory\Location\UpdateLocation;
use App\Models\Inventory\Location;
use App\Services\Organisation\SourceOrganisationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;

class FetchLocations extends FetchAction
{
    public string $commandSignature = 'fetch:locations {organisations?*} {--s|source_id=} {--d|db_suffix=}';

    #[NoReturn] public function handle(SourceOrganisationService $organisationSource, int $organisationSourceId): ?Location
    {
        if ($locationData = $organisationSource->fetchLocation($organisationSourceId)) {
            if ($location = Location::withTrashed()->where('source_id', $locationData['location']['source_id'])
                ->first()) {
                $location = UpdateLocation::run(
                    location:  $location,
                    modelData: $locationData['location']
                );
            } else {
                $location = StoreLocation::run(
                    parent:    $locationData['parent'],
                    modelData: $locationData['location'],
                );
            }


            return $location;
        }

        return null;
    }

    public function getModelsQuery(): Builder
    {
        return DB::connection('aurora')
            ->table('Location Dimension')
            ->select('Location Key as source_id')
            ->orderBy('source_id')
            ->when(app()->environment('testing'), function ($query) {
                return $query->limit(20);
            });
    }


    public function count(): ?int
    {
        return DB::connection('aurora')->table('Location Dimension')->count();
    }
}
