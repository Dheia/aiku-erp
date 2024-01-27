<?php
/*
 * Author: Artha <artha@aw-advantage.com>
 * Created: Thu, 20 Jul 2023 16:52:20 Central Indonesia Time, Sanur, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\FulfilmentOrder\UI;

use App\Actions\Fulfilment\Fulfilment\UI\ShowFulfilment;
use App\Actions\InertiaAction;
use App\Enums\UI\TabsAbbreviationEnum;
use App\Http\Resources\Sales\OrderResource;
use App\InertiaTable\InertiaTable;
use App\Models\CRM\Customer;
use App\Models\Market\Shop;
use App\Models\OMS\Order;
use App\Models\SysAdmin\Organisation;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Lorisleiva\Actions\ActionRequest;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\QueryBuilder;

class IndexFulfilmentOrders extends InertiaAction
{
    public function handle(Organisation|Shop|Customer $parent): LengthAwarePaginator
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->where('orders.number', '~*', "\y$value\y")
                    ->orWhere('orders.date', '=', $value);
            });
        });

        InertiaTable::updateQueryBuilderParameters(TabsAbbreviationEnum::ORDERS->value);

        return QueryBuilder::for(Order::class)
            ->defaultSort('orders.number')
            ->select([
                'orders.number',
                'orders.date',
                'orders.state',
                'orders.created_at',
                'orders.updated_at',
                'orders.slug',
                'shops.slug as shop_slug'
            ])
            ->leftJoin('order_stats', 'orders.id', 'order_stats.order_id')
            ->leftJoin('shops', 'orders.shop_id', 'shops.id')
            ->when($parent, function ($query) use ($parent) {
                if (class_basename($parent) == 'Shop') {
                    $query->where('orders.shop_id', $parent->id);
                } elseif (class_basename($parent) == 'Customer') {
                    $query->where('orders.customer_id', $parent->id);
                }
            })
            ->allowedSorts(['number', 'date'])
            ->allowedFilters([$globalSearch])
            ->paginate(
                perPage: $this->perPage ?? config('ui.table.records_per_page'),
                pageName: TabsAbbreviationEnum::ORDERS->value . 'Page'
            )
            ->withQueryString();
    }

    public function tableStructure($parent): Closure
    {
        return function (InertiaTable $table) use ($parent) {
            $table
                ->name(TabsAbbreviationEnum::ORDERS->value)
                ->pageName(TabsAbbreviationEnum::ORDERS->value . 'Page')

                ->withEmptyState(
                    match (class_basename($parent)) {
                        'Organisation' => [
                            'title'       => __("No orders found"),
                            'description' => __("In fact, is no even a shop yet 🤷🏽‍♂️"),
                            'count'       => $parent->crmStats->number_orders,
                        ],
                        'Customer' => [
                            'title'       => __("No orders found"),
                            'description' => __("In fact, is no even a shop yet 🤷🏽‍♂️"),
                            'count'       => $parent->orders()->count(),
                        ],
                        default => null,
                    }
                );

            $table->column(key: 'number', label: __('number'), canBeHidden: false, sortable: true, searchable: true);
            $table->column(key: 'date', label: __('date'), canBeHidden: false, sortable: true, searchable: true);
        };
    }

    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit = $request->user()->hasPermissionTo('shops.products.edit');

        return
            (
                $request->user()->tokenCan('root') or
                $request->user()->hasPermissionTo('shops.products.view')
            );
    }


    public function jsonResponse(LengthAwarePaginator $orders): AnonymousResourceCollection
    {
        return OrderResource::collection($orders);
    }


    public function htmlResponse(LengthAwarePaginator $orders, ActionRequest $request)
    {
        $parent = $request->route()->parameters() == [] ? app('currentTenant') : last($request->route()->parameters());

        return Inertia::render(
            'Fulfilment/Orders',
            [
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->parameters
                ),
                'title'    => __('orders'),
                'pageHead' => [
                    'title'  => __('orders'),
                    'create' => $this->canEdit && $request->route()->getName() == 'shops.show.orders.index' ? [
                        'route' => [
                            'name'       => 'shops.show.orders.create',
                            'parameters' => array_values($request->route()->originalParameters())
                        ],
                        'label' => __('order')
                    ] : false,
                ],
                'data' => OrderResource::collection($orders),


            ]
        )->table($this->tableStructure($parent));
    }


    public function inOrganisation(ActionRequest $request): LengthAwarePaginator
    {

        $this->initialisation($request);

        return $this->handle(parent: app('currentTenant'));
    }

    public function inShop(Shop $shop, ActionRequest $request): LengthAwarePaginator
    {
        $this->initialisation($request);

        return $this->handle(parent: $shop);
    }

    public function getBreadcrumbs(): array
    {
        return array_merge(
            ShowFulfilment::make()->getBreadcrumbs(),
            [
                [
                    'type'   => 'simple',
                    'simple' => [
                        'route' => [
                            'name' => 'grp.fulfilment.orders.index'
                        ],
                        'label' => __('orders'),
                        'icon'  => 'fal fa-bars',
                    ],

                ]
            ]
        );
    }
}
