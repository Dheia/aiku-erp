<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Wed, 15 Mar 2023 13:52:57 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Procurement\PurchaseOrder\UI;

use App\Actions\Helpers\History\IndexHistories;
use App\Actions\InertiaAction;
use App\Actions\Procurement\PurchaseOrderItem\UI\IndexPurchaseOrderItems;
use App\Actions\UI\Procurement\ProcurementDashboard;
use App\Enums\UI\PurchaseOrderTabsEnum;
use App\Http\Resources\Procurement\PurchaseOrderItemResource;
use App\Http\Resources\Procurement\PurchaseOrderResource;
use App\Http\Resources\SysAdmin\HistoryResource;
use App\Models\Procurement\PurchaseOrder;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

/**
 * @property PurchaseOrder $purchaseOrder
 */
class ShowPurchaseOrder extends InertiaAction
{
    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit = $request->user()->can('procurement.edit');

        return $request->user()->hasPermissionTo("procurement.view");
    }

    public function asController(PurchaseOrder $purchaseOrder, ActionRequest $request): void
    {
        $this->initialisation($request)->withTab(PurchaseOrderTabsEnum::values());
        $this->purchaseOrder = $purchaseOrder;

    }

    public function htmlResponse(): Response
    {
        $this->validateAttributes();

        return Inertia::render(
            'Procurement/PurchaseOrder',
            [
                'title'       => __('purchase order'),
                'breadcrumbs' => $this->getBreadcrumbs($this->purchaseOrder),
//                'navigation'                            => [
//                    'previous' => $this->getPrevious($this->purchaseOrder, $request),
//                    'next'     => $this->getNext($this->purchaseOrder, $request),
//                ],
                'pageHead'    => [
                    'icon'  =>
                        [
                            'icon'  => ['fal', 'people-arrows'],
                            'title' => __('warehouse')
                        ],
                    'title' => $this->purchaseOrder->number,
                    'edit'  => $this->canEdit ? [
                        'route' => [
                            'name'       => preg_replace('/show$/', 'edit', $this->routeName),
                            'parameters' => array_values($this->originalParameters)
                        ]
                    ] : false,

                ],
                'tabs' => [
                    'current'    => $this->tab,
                    'navigation' => PurchaseOrderTabsEnum::navigation()
                ],

                PurchaseOrderTabsEnum::SHOWCASE->value => $this->tab == PurchaseOrderTabsEnum::SHOWCASE->value ?
                    fn () => new PurchaseOrderResource(($this->purchaseOrder))
                    : Inertia::lazy(fn () => new PurchaseOrderResource(($this->purchaseOrder))),

                PurchaseOrderTabsEnum::ITEMS->value => $this->tab == PurchaseOrderTabsEnum::ITEMS->value ?
                    fn () => PurchaseOrderItemResource::collection(IndexPurchaseOrderItems::run($this->purchaseOrder))
                    : Inertia::lazy(fn () =>  PurchaseOrderItemResource::collection(IndexPurchaseOrderItems::run($this->purchaseOrder))),

                PurchaseOrderTabsEnum::HISTORY->value => $this->tab == PurchaseOrderTabsEnum::HISTORY->value ?
                    fn () => HistoryResource::collection(IndexHistories::run($this->purchaseOrder))
                    : Inertia::lazy(fn () => HistoryResource::collection(IndexHistories::run($this->purchaseOrder)))
            ]
        )->table(IndexPurchaseOrderItems::make()->tableStructure())
            ->table(IndexHistories::make()->tableStructure());
    }

    public function jsonResponse(): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchaseOrder);
    }

    public function getBreadcrumbs(PurchaseOrder $purchaseOrder, $suffix = null): array
    {
        return array_merge(
            (new ProcurementDashboard())->getBreadcrumbs(),
            [
                [
                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => [
                                'name' => 'procurement.purchase-orders.index',
                            ],
                            'label' => __('purchaseOrder')
                        ],
                        'model' => [
                            'route' => [
                                'name'       => 'procurement.purchase-orders.show',
                                'parameters' => [$purchaseOrder->slug]
                            ],
                            'label' => $purchaseOrder->number,
                        ],
                    ],
                    'suffix' => $suffix,

                ],
            ]
        );
    }

    public function getPrevious(PurchaseOrder $purchaseOrder, ActionRequest $request): ?array
    {
        $previous = PurchaseOrder::where('number', '<', $purchaseOrder->number)->orderBy('number', 'desc')->first();
        return $this->getNavigation($previous, $request->route()->getName());

    }

    public function getNext(PurchaseOrder $purchaseOrder, ActionRequest $request): ?array
    {
        $next = PurchaseOrder::where('number', '>', $purchaseOrder->number)->orderBy('number')->first();
        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?PurchaseOrder $purchaseOrder, string $routeName): ?array
    {
        if(!$purchaseOrder) {
            return null;
        }

        return match ($routeName) {
            'procurement.purchase-orders.show'=> [
                'label'=> $purchaseOrder->number,
                'route'=> [
                    'name'      => $routeName,
                    'parameters'=> [
                        'purchaseOrder'=> $purchaseOrder->number
                    ]

                ]
            ]
        };
    }
}
