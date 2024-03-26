<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 29 Jan 2024 10:06:36 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\Fulfilment;

use App\Actions\Fulfilment\Fulfilment\Hydrators\FulfilmentHydratePallets;
use App\Actions\Fulfilment\Fulfilment\Hydrators\FulfilmentHydrateWarehouses;
use App\Actions\HydrateModel;


use App\Models\Fulfilment\Fulfilment;
use Illuminate\Support\Collection;

class HydrateFulfilment extends HydrateModel
{
    public string $commandSignature = 'fulfilment:hydrate {organisations?*} {--s|slugs=}';

    public function handle(Fulfilment $fulfilment): void
    {
        FulfilmentHydrateWarehouses::run($fulfilment);
        FulfilmentHydratePallets::run($fulfilment);
    }

    protected function getModel(string $slug): Fulfilment
    {
        return Fulfilment::where('slug', $slug)->first();
    }

    protected function getAllModels(): Collection
    {
        return Fulfilment::all();
    }
}
