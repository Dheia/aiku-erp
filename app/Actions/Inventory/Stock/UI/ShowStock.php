<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Wed, 15 Mar 2023 15:27:27 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Inventory\Stock\UI;

use App\Actions\Helpers\History\IndexHistory;
use App\Actions\InertiaAction;
use App\Actions\Inventory\StockFamily\UI\ShowStockFamily;
use App\Actions\UI\Inventory\InventoryDashboard;
use App\Enums\UI\StockTabsEnum;
use App\Http\Resources\History\HistoryResource;
use App\Http\Resources\Inventory\StockResource;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockFamily;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

class ShowStock extends InertiaAction
{
    public function handle(Stock $stock): Stock
    {
        return $stock;
    }

    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit   = $request->user()->hasPermissionTo('inventory.stocks.edit');
        $this->canDelete = $request->user()->hasPermissionTo('inventory.stocks.edit');

        return $request->user()->hasPermissionTo("inventory.stocks.view");
    }

    public function asController(Stock $stock, ActionRequest $request): Stock
    {
        $this->initialisation($request)->withTab(StockTabsEnum::values());

        return $this->handle($stock);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inStockFamily(StockFamily $stockFamily, Stock $stock, ActionRequest $request): Stock
    {
        $this->initialisation($request);

        return $this->handle($stock);
    }

    public function htmlResponse(Stock $stock, ActionRequest $request): Response
    {

        return Inertia::render(
            'Inventory/Stock',
            [
                 'title'       => __('stock'),
                 'breadcrumbs' => $this->getBreadcrumbs(
                     $request->route()->getName(),
                     $request->route()->parameters
                 ),
                 'navigation'  => [
                     'previous' => $this->getPrevious($stock, $request),
                     'next'     => $this->getNext($stock, $request),
                 ],
                 'pageHead'    => [
                     'icon'    => [
                         'title' => __('skus'),
                         'icon'  => 'fal fa-box'
                     ],
                     'title'   => $stock->slug,
                     'actions' => [
                         $this->canEdit ? [
                             'type'  => 'button',
                             'style' => 'edit',
                             'route' => [
                                 'name'       => preg_replace('/show$/', 'edit', $request->route()->getName()),
                                 'parameters' => array_values($request->route()->originalParameters())
                             ]
                         ] : false,
                         $this->canDelete ? [
                             'type'  => 'button',
                             'style' => 'delete',
                             'route' => [
                                 'name'       => 'grp.inventory.stock-families.show.stocks.remove',
                                 'parameters' => array_values($request->route()->originalParameters())
                             ]

                         ] : false
                     ]
                 ],
                 'tabs'=> [
                     'current'    => $this->tab,
                     'navigation' => StockTabsEnum::navigation()

                 ],
                 StockTabsEnum::SHOWCASE->value => $this->tab == StockTabsEnum::SHOWCASE->value ?
                     fn () => GetStockShowcase::run($stock)
                     : Inertia::lazy(fn () => GetStockShowcase::run($stock)),

                 StockTabsEnum::HISTORY->value => $this->tab == StockTabsEnum::HISTORY->value ?
                     fn () => HistoryResource::collection(IndexHistory::run($stock))
                     : Inertia::lazy(fn () => HistoryResource::collection(IndexHistory::run($stock)))


             ]
        )->table();
    }


    public function jsonResponse(Stock $stock): StockResource
    {
        return new StockResource($stock);
    }

    public function getBreadcrumbs(string $routeName, array $routeParameters, $suffix = null): array
    {
        $headCrumb = function (Stock $stock, array $routeParameters, $suffix) {
            return [
                [
                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => $routeParameters['index'],
                            'label' => __('SKUs')
                        ],
                        'model' => [
                            'route' => $routeParameters['model'],
                            'label' => $stock->slug,
                        ],
                    ],
                    'suffix' => $suffix,

                ],
            ];
        };
        return match ($routeName) {
            'grp.inventory.stocks.show' =>
            array_merge(
                (new InventoryDashboard())->getBreadcrumbs(),
                $headCrumb(
                    $routeParameters['stock'],
                    [
                        'index' => [
                            'name'       => 'grp.inventory.stocks.index',
                            'parameters' => []
                        ],
                        'model' => [
                            'name'       => 'grp.inventory.stocks.show',
                            'parameters' => [
                                $routeParameters['stock']->slug
                            ]
                        ]
                    ],
                    $suffix
                )
            ),
            'grp.inventory.stock-families.show.stocks.show' =>
            array_merge(
                (new ShowStockFamily())->getBreadcrumbs($routeParameters['stockFamily']),
                $headCrumb(
                    $routeParameters['stock'],
                    [
                        'index' => [
                            'name'       => 'grp.inventory.stock-families.show.stocks.index',
                            'parameters' => [
                                $routeParameters['stockFamily']->slug
                            ]
                        ],
                        'model' => [
                            'name'       => 'grp.inventory.stock-families.show.stocks.show',
                            'parameters' => [
                                $routeParameters['stockFamily']->slug,
                                $routeParameters['stock']->slug
                            ]
                        ]
                    ],
                    $suffix
                )
            ),
            default => []
        };
    }

    public function getPrevious(Stock $stock, ActionRequest $request): ?array
    {
        $previous = Stock::where('code', '<', $stock->code)->when(true, function ($query) use ($stock, $request) {
            if ($request->route()->getName() == 'grp.inventory.stock-families.show.stocks.show') {
                $query->where('stock_family_id', $stock->stockFamily->id);
            }
        })->orderBy('code', 'desc')->first();
        return $this->getNavigation($previous, $request->route()->getName());
    }

    public function getNext(Stock $stock, ActionRequest $request): ?array
    {
        $next = Stock::where('code', '>', $stock->code)->when(true, function ($query) use ($stock, $request) {
            if ($request->route()->getName() == 'grp.inventory.stock-families.show.stocks.show') {
                $query->where('stock_family_id', $stock->stockFamily->id);
            }
        })->orderBy('code')->first();

        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?Stock $stock, string $routeName): ?array
    {
        if (!$stock) {
            return null;
        }

        return match ($routeName) {
            'grp.inventory.stocks.show' => [
                'label' => $stock->name,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'stock' => $stock->slug
                    ]
                ]
            ],
            'grp.inventory.stock-families.show.stocks.show' => [
                'label' => $stock->name,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'stockFamily'   => $stock->stockFamily->slug,
                        'stock'         => $stock->slug
                    ]

                ]
            ]
        };
    }
}
