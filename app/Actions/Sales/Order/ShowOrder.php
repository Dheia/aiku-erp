<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Wed, 12 Oct 2022 17:04:31 Central European Summer, Benalmádena, Malaga, Spain
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Actions\Sales\Order;

use App\Actions\Accounting\Invoice\IndexInvoices;
use App\Actions\Accounting\Payment\UI\IndexPayments;
use App\Actions\Dispatch\DeliveryNote\IndexDeliveryNotes;
use App\Actions\InertiaAction;
use App\Actions\Marketing\Shop\UI\ShowShop;
use App\Actions\Sales\Order\UI\HasUIOrder;
use App\Actions\UI\Dashboard\Dashboard;
use App\Enums\UI\OrderTabsEnum;
use App\Http\Resources\Accounting\InvoiceResource;
use App\Http\Resources\Accounting\PaymentResource;
use App\Http\Resources\Delivery\DeliveryNoteResource;
use App\Http\Resources\Sales\OrderResource;
use App\Models\Marketing\Shop;
use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

/**
 * @property Order $order
 */
class ShowOrder extends InertiaAction
{
    use HasUIOrder;

    public function handle(Order $order): Order
    {
        return $order;
    }

    public function authorize(ActionRequest $request): bool
    {
        //TODO Change permission
        $this->canEdit = $request->user()->can('shops.orders.edit');

        return $request->user()->hasPermissionTo("shops.orders.view");
    }

    public function inTenant(Order $order, ActionRequest $request): Order
    {
        $this->initialisation($request)->withTab(OrderTabsEnum::values());
        return $this->handle($order);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inShop(Shop $shop, Order $order, ActionRequest $request): Order
    {
        $this->initialisation($request)->withTab(OrderTabsEnum::values());
        return $this->handle($order);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inCustomerInTenant(Customer $customer, Order $order, ActionRequest $request): Order
    {
        $this->initialisation($request)->withTab(OrderTabsEnum::values());
        return $this->handle($order);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inCustomerInShop(Shop $shop, Customer $customer, Order $order, ActionRequest $request): Order
    {
        $this->initialisation($request)->withTab(OrderTabsEnum::values());
        return $this->handle($order);
    }

    public function htmlResponse(Order $order, ActionRequest $request): Response
    {
        $this->validateAttributes();


        return Inertia::render(
            'Marketing/Order',
            [
                'title'       => __('order'),
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->parameters(),
                ),
                'navigation'                            => [
                    'previous' => $this->getPrevious($order, $request),
                    'next'     => $this->getNext($order, $request),
                ],
                'pageHead'    => [
                    'title' => $order->number,
                ],
                'tabs'        => [
                    'current'    => $this->tab,
                    'navigation' => OrderTabsEnum::navigation()
                ],

                OrderTabsEnum::PAYMENTS->value => $this->tab == OrderTabsEnum::PAYMENTS->value ?
                    fn () => PaymentResource::collection(IndexPayments::run($this->order))
                    : Inertia::lazy(fn () => PaymentResource::collection(IndexPayments::run($this->order))),

                OrderTabsEnum::INVOICES->value => $this->tab == OrderTabsEnum::INVOICES->value ?
                    fn () => InvoiceResource::collection(IndexInvoices::run($this->order))
                    : Inertia::lazy(fn () => InvoiceResource::collection(IndexInvoices::run($this->order))),

                OrderTabsEnum::DELIVERY_NOTES->value => $this->tab == OrderTabsEnum::DELIVERY_NOTES->value ?
                    fn () => DeliveryNoteResource::collection(IndexDeliveryNotes::run($this->order))
                    : Inertia::lazy(fn () => DeliveryNoteResource::collection(IndexDeliveryNotes::run($this->order))),

            ]
        )->table(IndexPayments::make()->tableStructure())
            ->table(IndexInvoices::make()->tableStructure($order))
            ->table(IndexDeliveryNotes::make()->tableStructure($order));
    }

    public function prepareForValidation(ActionRequest $request): void
    {
        $this->fillFromRequest($request);

        $this->set('canEdit', $request->user()->can('hr.edit'));
        $this->set('canViewUsers', $request->user()->can('users.view'));
    }

    public function jsonResponse(Order $order): OrderResource
    {
        return new OrderResource($order);
    }

    public function getBreadcrumbs(string $routeName, array $routeParameters, string $suffix = ''): array
    {
        $headCrumb = function (Order $order, array $routeParameters, string $suffix) {
            return [
                [

                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => $routeParameters['index'],
                            'label' => __('orders')
                        ],
                        'model' => [
                            'route' => $routeParameters['model'],
                            'label' => $order->slug,
                        ],

                    ],
                    'suffix'=> $suffix

                ],
            ];
        };
        return match ($routeName) {
            'orders.show',
            'orders.edit' =>

            array_merge(
                Dashboard::make()->getBreadcrumbs(),
                $headCrumb(
                    $routeParameters['order'],
                    [
                        'index' => [
                            'name'       => 'customers.index',
                            'parameters' => []
                        ],
                        'model' => [
                            'name'       => 'orders.show',
                            'parameters' => [$routeParameters['order']->slug]
                        ]
                    ],
                    $suffix
                ),
            ),


            'shops.show.orders.show',
            'shops.show.orders.edit'
            => array_merge(
                (new ShowShop())->getBreadcrumbs($routeParameters),
                $headCrumb(
                    $routeParameters['order'],
                    [
                        'index' => [
                            'name'       => 'shops.show.orders.index',
                            'parameters' => [
                                $routeParameters['shop']->slug,
                            ]
                        ],
                        'model' => [
                            'name'       => 'shops.show.orders.show',
                            'parameters' => [
                                $routeParameters['shop']->slug,
                                $routeParameters['order']->slug
                            ]
                        ]
                    ],
                    $suffix
                )
            ),
            default => []
        };
    }

    public function getPrevious(Order $order, ActionRequest $request): ?array
    {

        $previous = Order::where('number', '<', $order->number)->when(true, function ($query) use ($order, $request) {
            if ($request->route()->getName() == 'shops.show.orders.show') {
                $query->where('orders.shop_id', $order->shop_id);
            }
        })->orderBy('number', 'desc')->first();

        return $this->getNavigation($previous, $request->route()->getName());

    }

    public function getNext(Order $order, ActionRequest $request): ?array
    {
        $next = Order::where('number', '>', $order->number)->when(true, function ($query) use ($order, $request) {
            if ($request->route()->getName() == 'shops.show.orders.show') {
                $query->where('orders.shop_id', $order->shop_id);
            }
        })->orderBy('number')->first();

        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?Order $order, string $routeName): ?array
    {
        if(!$order) {
            return null;
        }

        return match ($routeName) {
            'orders.show' ,
            'shops.orders.show'=> [
                'label'=> $order->number,
                'route'=> [
                    'name'      => $routeName,
                    'parameters'=> [
                        'order'=> $order->slug
                    ]

                ]
            ],
            'shops.show.orders.show'=> [
                'label'=> $order->number,
                'route'=> [
                    'name'      => $routeName,
                    'parameters'=> [
                        'shop'  => $order->shop->slug,
                        'order' => $order->slug
                    ]

                ]
            ]
        };
    }
}
