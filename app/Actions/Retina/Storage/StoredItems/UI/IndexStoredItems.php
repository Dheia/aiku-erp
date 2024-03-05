<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 25 May 2023 21:14:38 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Retina\Storage\StoredItems\UI;

use App\Actions\RetinaAction;
use App\Actions\UI\Retina\Storage\ShowStorageDashboard;
use App\Enums\UI\TabsAbbreviationEnum;
use App\Http\Resources\Fulfilment\StoredItemResource;
use App\Models\Fulfilment\FulfilmentCustomer;
use App\Models\Fulfilment\StoredItem;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;
use App\InertiaTable\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\QueryBuilder;

class IndexStoredItems extends RetinaAction
{
    public function handle(FulfilmentCustomer $parent, $prefix = null): LengthAwarePaginator
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->where('slug', 'ILIKE', "%$value%");
            });
        });

        if ($prefix) {
            InertiaTable::updateQueryBuilderParameters($prefix);
        }

        return QueryBuilder::for(StoredItem::class)
            ->defaultSort('slug')
            ->when($parent, function ($query) use ($parent) {
                if(class_basename($parent) == "FulfilmentCustomer") {
                    $query->where('fulfilment_customer_id', $parent->id);
                }
            })
            ->allowedSorts(['slug', 'state'])
            ->allowedFilters([$globalSearch, 'slug', 'state'])
            ->withPaginator($prefix)
            ->withQueryString();
    }

    public function tableStructure($parent): Closure
    {
        return function (InertiaTable $table) use ($parent) {
            $table
                ->name(TabsAbbreviationEnum::STORED_ITEMS->value)
                ->pageName(TabsAbbreviationEnum::STORED_ITEMS->value.'Page')

                ->withGlobalSearch()
                ->withEmptyState(
                    [
                        'title'         => __("No stored items found"),
                        'count'         => $parent->count(),
                        'description'   => __("No items stored in any pallets")
                    ]
                )
                ->column(key: 'reference', label: __('reference'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'customer_name', label: __('Customer Name'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'location', label: __('Location'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'state', label: __('State'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'status', label: __('Status'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'notes', label: __('Notes'), canBeHidden: false, sortable: true, searchable: true)
                ->defaultSort('slug');
        };
    }

    public function authorize(ActionRequest $request): bool
    {
        // TODO: Need to check the permissions
        return true;
        return
            (
                $request->user()->tokenCan('root') or
                $request->user()->hasPermissionTo("human-resources.{$this->organisation->id}.view")
            );
    }


    public function jsonResponse(LengthAwarePaginator $storedItems): AnonymousResourceCollection
    {
        return StoredItemResource::collection($storedItems);
    }


    public function htmlResponse(LengthAwarePaginator $storedItems): Response
    {
        return Inertia::render(
            'Fulfilment/StoredItems',
            [
                'breadcrumbs' => $this->getBreadcrumbs(),
                'title'       => __('stored items'),
                'pageHead'    => [
                    'title'   => __('stored items'),
                    'actions' => [
                        'buttons' => [
                            'route' => [
                                'name'       => 'grp.org.hr.employees.create',
                                'parameters' => array_values($request->route()->originalParameters())
                            ],
                            'label' => __('stored items')
                        ]
                    ],
                ],
                'data' => StoredItemResource::collection($storedItems),
            ]
        )->table($this->tableStructure($storedItems));
    }

    public function asController(ActionRequest $request): LengthAwarePaginator
    {
        $fulfilmentCustomer = $request->user()->customer->fulfilmentCustomer;

        $this->initialisation($request);

        return $this->handle($fulfilmentCustomer);
    }

    public function getBreadcrumbs(): array
    {
        return array_merge(
            ShowStorageDashboard::make()->getBreadcrumbs(),
            [
                [
                    'type'   => 'simple',
                    'simple' => [
                        'route' => [
                            'name' => 'grp.fulfilment.stored-items.index'
                        ],
                        'label' => __('stored items'),
                        'icon'  => 'fal fa-bars',
                    ],

                ]
            ]
        );
    }
}
