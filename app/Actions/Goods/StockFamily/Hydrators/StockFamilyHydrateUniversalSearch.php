<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Sat, 23 Mar 2024 12:24:25 Malaysia Time, Mexico City, Mexico
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Goods\StockFamily\Hydrators;

use App\Models\SupplyChain\StockFamily;
use Lorisleiva\Actions\Concerns\AsAction;

class StockFamilyHydrateUniversalSearch
{
    use AsAction;


    public function handle(StockFamily $stockFamily): void
    {
        $stockFamily->universalSearch()->updateOrCreate(
            [],
            [
                'section'     => 'inventory',
                'title'       => join(' ', array_unique([$stockFamily->code, $stockFamily->name])),
                'description' => ''
            ]
        );
    }

}
