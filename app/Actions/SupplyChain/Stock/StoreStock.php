<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Fri, 29 Oct 2021 12:56:07 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2021, Inikoo
 *  Version 4.0
 */

namespace App\Actions\SupplyChain\Stock;

use App\Actions\GrpAction;
use App\Actions\SupplyChain\Stock\Hydrators\StockHydrateUniversalSearch;
use App\Actions\SupplyChain\StockFamily\Hydrators\StockFamilyHydrateStocks;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateInventory;
use App\Models\SupplyChain\Stock;
use App\Models\SupplyChain\StockFamily;
use App\Models\SysAdmin\Group;
use App\Rules\AlphaDashDot;
use App\Rules\IUnique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;

class StoreStock extends GrpAction
{
    public function handle(Group $group, $modelData): Stock
    {
        /** @var Stock $stock */
        $stock = $group->stocks()->create($modelData);
        $stock->stats()->create();
        GroupHydrateInventory::dispatch($group);
        if ($stock->stock_family_id) {
            StockFamilyHydrateStocks::dispatch($stock->stockFamily)->delay($this->hydratorsDelay);
        }
        StockHydrateUniversalSearch::dispatch($stock);


        return $stock;
    }

    public function rules(): array
    {
        return [
            'code'            => [
                'required',
                'max:64',
                new AlphaDashDot(),
                Rule::notIn(['export', 'create', 'upload']),
                new IUnique(
                    table: 'stocks',
                    extraConditions: [
                        ['column' => 'group_id', 'value' => $this->group->id],
                    ]
                ),
            ],
            'name'            => ['required', 'string', 'max:255'],
            'stock_family_id' => ['sometimes', 'nullable', 'exists:stock_families,id'],
            'source_id'       => ['sometimes', 'nullable', 'string'],
            'source_slug'     => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function action(Group $group, array $modelData, int $hydratorDelay = 0): Stock
    {
        $this->hydratorsDelay = $hydratorDelay;
        $this->initialisation($group, $modelData);

        return $this->handle($group, $this->validatedData);
    }

    public function inStockFamily(StockFamily $stockFamily, ActionRequest $request): Stock
    {
        $this->fill(
            [
                'stock_family_id' => $stockFamily->id
            ]
        );
        $this->initialisation(group(), $request);


        return $this->handle(group(), $this->validatedData);
    }

    public function htmlResponse(Stock $stock): RedirectResponse
    {
        if (!$stock->stock_family_id) {
            return Redirect::route('grp.org.inventory.org-stock-families.show.stocks.show', [
                $stock->stockFamily->slug,
                $stock->slug
            ]);
        } else {
            return Redirect::route('grp.org.inventory.org-stocks.show', [
                $stock->slug
            ]);
        }
    }
}
