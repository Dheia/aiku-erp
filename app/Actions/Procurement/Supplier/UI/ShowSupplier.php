<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Wed, 15 Mar 2023 14:15:00 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Procurement\Supplier\UI;

use App\Actions\Helpers\History\IndexHistories;
use App\Actions\InertiaAction;
use App\Actions\Procurement\Agent\UI\ShowAgent;
use App\Actions\Procurement\PurchaseOrder\UI\IndexPurchaseOrders;
use App\Actions\Procurement\SupplierDelivery\UI\IndexSupplierDeliveries;
use App\Actions\Procurement\SupplierProduct\UI\IndexSupplierProducts;
use App\Actions\UI\Procurement\ProcurementDashboard;
use App\Enums\UI\SupplierTabsEnum;
use App\Http\Resources\History\HistoryResource;
use App\Http\Resources\Procurement\PurchaseOrderResource;
use App\Http\Resources\Procurement\SupplierDeliveryResource;
use App\Http\Resources\Procurement\SupplierProductResource;
use App\Http\Resources\Procurement\SupplierResource;
use App\Models\Procurement\Agent;
use App\Models\Procurement\Supplier;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

/**
 * @property Supplier $supplier
 */
class ShowSupplier extends InertiaAction
{
    public function handle(Supplier $supplier): Supplier
    {
        return $supplier;
    }


    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit   = $request->user()->can('procurement.suppliers.edit');
        $this->canDelete = $request->user()->can('procurement.suppliers.edit');

        return $request->user()->hasPermissionTo("procurement.view");
    }

    public function asController(Supplier $supplier, ActionRequest $request): Supplier
    {
        $this->initialisation($request)->withTab(SupplierTabsEnum::values());

        return $this->handle($supplier);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inAgent(Agent $agent, Supplier $supplier, ActionRequest $request): Supplier
    {
        $this->initialisation($request)->withTab(SupplierTabsEnum::values());

        return $this->handle($supplier);
    }

    public function htmlResponse(Supplier $supplier, ActionRequest $request): Response
    {

        return Inertia::render(
            'Procurement/Supplier',
            [
                'title'       => __('supplier'),
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->parameters
                ),
                'navigation'    => [
                    'previous'  => $this->getPrevious($supplier, $request),
                    'next'      => $this->getNext($supplier, $request),
                ],
                'pageHead'    => [
                    'icon'          =>
                        [
                            'icon'  => 'fal fa-person-dolly',
                            'title' => __('supplier')
                        ],
                    'title'         => $supplier->name,
                    'actions'       => [
                        $this->canEdit ? [
                            'type'  => 'button',
                            'style' => 'edit',
                            'route' => [
                                'name'       => preg_replace('/show$/', 'edit', $this->routeName),
                                'parameters' => array_values($this->originalParameters)
                            ]
                        ] : false,
                        $this->canDelete ? [
                            'type'  => 'button',
                            'style' => 'delete',
                            'route' => [
                                'name'       => 'procurement.suppliers.remove',
                                'parameters' => array_values($this->originalParameters)
                            ]
                        ] : false,
                        $this->canEdit && $supplier->owner_type=='Organisation' ? [
                            'type'  => 'button',
                            'style' => 'create',
                            'route' => [
                                'name'       => 'procurement.suppliers.show.purchase-orders.create',
                                'parameters' => array_values($this->originalParameters)
                            ],
                            'label' => __('purchase order')
                        ] : false,
                    ],
                    'meta'          => [
                        [
                            'name'     => trans_choice('Purchases|Sales', $supplier->stats->number_open_purchase_orders),
                            'number'   => $supplier->stats->number_open_purchase_orders,
                            'href'     => [
                                'procurement.supplier-products.show',
                                $supplier->slug
                            ],
                            'leftIcon' => [
                                'icon'    => 'fal fa-person-dolly',
                                'tooltip' => __('sales')
                            ]
                        ],
                        [
                            'name'     => trans_choice('product|products', $supplier->stats->number_supplier_products),
                            'number'   => $supplier->stats->number_supplier_products,
                            'href'     => [
                                'procurement.supplier-products.show',
                                $supplier->slug
                            ],
                            'leftIcon' => [
                                'icon'    => 'fal fa-box-usd',
                                'tooltip' => __('products')
                            ]
                        ],
                    ]

                ],
                'tabs'        => [
                    'current'    => $this->tab,
                    'navigation' => SupplierTabsEnum::navigation()
                ],

                SupplierTabsEnum::SHOWCASE->value => $this->tab == SupplierTabsEnum::SHOWCASE->value ?
                    fn () => GetSupplierShowcase::run($supplier)
                    : Inertia::lazy(fn () => GetSupplierShowcase::run($supplier)),

                SupplierTabsEnum::PURCHASES_SALES->value => $this->tab == SupplierTabsEnum::PURCHASES_SALES->value ?
                    fn () => SupplierProductResource::collection(IndexSupplierProducts::run(
                        parent: $supplier,
                        prefix: 'supplier_products'
                    ))
                    : Inertia::lazy(fn () => SupplierProductResource::collection(IndexSupplierProducts::run($supplier))),

                SupplierTabsEnum::SUPPLIER_PRODUCTS->value => $this->tab == SupplierTabsEnum::SUPPLIER_PRODUCTS->value ?
                    fn () => SupplierProductResource::collection(IndexSupplierProducts::run($supplier))
                    : Inertia::lazy(fn () => SupplierProductResource::collection(IndexSupplierProducts::run($supplier))),

                SupplierTabsEnum::PURCHASE_ORDERS->value => $this->tab == SupplierTabsEnum::PURCHASE_ORDERS->value ?
                    fn () => PurchaseOrderResource::collection(IndexPurchaseOrders::run($supplier))
                    : Inertia::lazy(fn () => PurchaseOrderResource::collection(IndexPurchaseOrders::run($supplier))),

                SupplierTabsEnum::DELIVERIES->value => $this->tab == SupplierTabsEnum::DELIVERIES->value ?
                    fn () => SupplierDeliveryResource::collection(IndexSupplierDeliveries::run($supplier))
                    : Inertia::lazy(fn () => SupplierDeliveryResource::collection(IndexSupplierDeliveries::run($supplier))),

                SupplierTabsEnum::HISTORY->value => $this->tab == SupplierTabsEnum::HISTORY->value ?
                    fn () => HistoryResource::collection(IndexHistories::run($supplier))
                    : Inertia::lazy(fn () => HistoryResource::collection(IndexHistories::run($supplier)))
            ]
        )->table(IndexSupplierProducts::make()->tableStructure())
        ->table(IndexSupplierProducts::make()->tableStructure())
        ->table(IndexPurchaseOrders::make()->tableStructure())
        ->table(IndexSupplierDeliveries::make()->tableStructure())
        ->table(IndexHistories::make()->tableStructure());
    }


    public function getBreadcrumbs(string $routeName, array $routeParameters, string $suffix = ''): array
    {
        $headCrumb = function (Supplier $supplier, array $routeParameters, string $suffix) {
            return [
                [

                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => $routeParameters['index'],
                            'label' => __('suppliers')
                        ],
                        'model' => [
                            'route' => $routeParameters['model'],
                            'label' => $supplier->name,
                        ],

                    ],
                    'suffix'=> $suffix

                ],
            ];
        };

        return match ($routeName) {
            'procurement.suppliers.show' =>
            array_merge(
                ProcurementDashboard::make()->getBreadcrumbs(),
                $headCrumb(
                    $routeParameters['supplier'],
                    [
                        'index' => [
                            'name'       => 'procurement.suppliers.index',
                            'parameters' => []
                        ],
                        'model' => [
                            'name'       => 'procurement.suppliers.show',
                            'parameters' => [$routeParameters['supplier']->slug]
                        ]
                    ],
                    $suffix
                ),
            ),
            'procurement.agents.show.suppliers.show' =>
            array_merge(
                (new ShowAgent())->getBreadcrumbs(
                    ['agent'=> $routeParameters['agent']]
                ),
                $headCrumb(
                    $routeParameters['supplier'],
                    [
                        'index' => [
                            'name'       => 'procurement.agents.show.suppliers.index',
                            'parameters' => [
                                $routeParameters['agent']->slug,
                            ]
                        ],
                        'model' => [
                            'name'       => 'procurement.agents.show.suppliers.show',
                            'parameters' => [
                                $routeParameters['agent']->slug,
                                $routeParameters['supplier']->slug
                            ]
                        ]
                    ],
                    $suffix
                )
            ),
            default => []
        };
    }



    public function jsonResponse(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    public function getPrevious(Supplier $supplier, ActionRequest $request): ?array
    {

        $previous = Supplier::where('code', '<', $supplier->code)->when(true, function ($query) use ($supplier, $request) {
            if ($request->route()->getName() == 'procurement.agents.show.suppliers.show') {
                $query->where('suppliers.agent_id', $supplier->agent_id);
            }
        })->orderBy('code', 'desc')->first();

        return $this->getNavigation($previous, $request->route()->getName());

    }

    public function getNext(Supplier $supplier, ActionRequest $request): ?array
    {
        $next = Supplier::where('code', '>', $supplier->code)->when(true, function ($query) use ($supplier, $request) {
            if ($request->route()->getName() == 'procurement.agents.show.suppliers.show') {
                $query->where('suppliers.agent_id', $supplier->agent_id);
            }
        })->orderBy('code')->first();

        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?Supplier $supplier, string $routeName): ?array
    {
        if(!$supplier) {
            return null;
        }

        return match ($routeName) {
            'procurement.suppliers.show'=> [
                'label'=> $supplier->code,
                'route'=> [
                    'name'      => $routeName,
                    'parameters'=> [
                        'supplier'  => $supplier->slug
                    ]

                ]
            ],
            'procurement.agents.show.suppliers.show' => [
                'label'=> $supplier->code,
                'route'=> [
                    'name'      => $routeName,
                    'parameters'=> [
                        'agent'     => $supplier->agent->slug,
                        'supplier'  => $supplier->slug
                    ]

                ]
            ]
        };
    }

}
