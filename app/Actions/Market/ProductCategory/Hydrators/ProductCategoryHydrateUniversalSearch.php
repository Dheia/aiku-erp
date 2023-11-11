<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Fri, 10 Mar 2023 11:05:41 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Market\ProductCategory\Hydrators;

use App\Actions\Traits\WithOrganisationJob;
use App\Models\Market\ProductCategory;
use Lorisleiva\Actions\Concerns\AsAction;

class ProductCategoryHydrateUniversalSearch
{
    use AsAction;
    use WithOrganisationJob;

    public function handle(ProductCategory $productCategory): void
    {
        $productCategory->universalSearch()->updateOrCreate(
            [],
            [
                'section'     => 'shops',
                'title'       => $productCategory->name,
                'description' => $productCategory->code
            ]
        );
    }

}
