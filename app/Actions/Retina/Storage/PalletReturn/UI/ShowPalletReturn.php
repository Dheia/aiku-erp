<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 13 Mar 2023 17:41:13 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Retina\Storage\PalletReturn\UI;

use App\Actions\Fulfilment\Pallet\UI\IndexPallets;
use App\Actions\RetinaAction;
use App\Actions\UI\Retina\Storage\ShowStorageDashboard;
use App\Enums\Fulfilment\PalletReturn\PalletReturnStateEnum;
use App\Enums\UI\PalletReturnTabsEnum;
use App\Http\Resources\Fulfilment\PalletReturnResource;
use App\Http\Resources\Fulfilment\PalletReturnsResource;
use App\Http\Resources\Fulfilment\PalletsResource;
use App\Models\Fulfilment\FulfilmentCustomer;
use App\Models\Fulfilment\PalletReturn;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

class ShowPalletReturn extends RetinaAction
{
    private FulfilmentCustomer $parent;

    public function handle(PalletReturn $palletReturn): PalletReturn
    {
        return $palletReturn;
    }


    public function asController(PalletReturn $palletReturn, ActionRequest $request): PalletReturn
    {
        $this->parent = $request->user()->customer->fulfilmentCustomer;
        $this->initialisation($request)->withTab(PalletReturnTabsEnum::values());

        return $this->handle($palletReturn);
    }

    public function htmlResponse(PalletReturn $palletReturn, ActionRequest $request): Response
    {
        return Inertia::render(
            'Storage/RetinaPalletReturn',
            [
                'title'       => __('pallet return'),
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->originalParameters()
                ),
                'navigation' => [
                    'previous' => $this->getPrevious($palletReturn, $request),
                    'next'     => $this->getNext($palletReturn, $request),
                ],
                'pageHead' => [
                    'title'     => __($palletReturn->reference),
                    'icon'      => [
                        'icon'  => ['fal', 'fa-truck-couch'],
                        'title' => __($palletReturn->reference)
                    ],
                    'actions' => $palletReturn->state == PalletReturnStateEnum::IN_PROCESS ? [
                        [
                            'type'  => 'button',
                            'style' => 'secondary',
                            'icon'  => 'fal fa-plus',
                            'label' => __('add pallet'),
                            'route' => [
                                'name'       => 'retina.models.pallet-return.pallet.store',
                                'parameters' => [
                                    'palletReturn'       => $palletReturn->reference
                                ]
                            ]
                        ],
                        $palletReturn->pallets()->count() > 0 ? [
                            'type'    => 'button',
                            'style'   => 'save',
                            'tooltip' => __('submit'),
                            'label'   => __('submit'),
                            'key'     => 'action',
                            'route'   => [
                                'method'     => 'post',
                                'name'       => 'retina.models.pallet-return.submit',
                                'parameters' => [
                                    'palletReturn'       => $palletReturn->id
                                ]
                            ]
                        ] : [],
                        $palletReturn->state != PalletReturnStateEnum::DISPATCHED ? [
                            'type'    => 'button',
                            'style'   => 'cancel',
                            'tooltip' => __('cancel'),
                            'label'   => __('cancel'),
                            'key'     => 'action',
                            'route'   => [
                                'method'     => 'post',
                                'name'       => 'retina.models.pallet-return.cancel',
                                'parameters' => [
                                    'palletReturn'       => $palletReturn->id
                                ]
                            ]
                        ] : []
                    ] : [],
                ],

                'updateRoute' => [
                    'route' => [
                        'name'       => 'grp.models.fulfilment-customer.pallet-return.timeline.update',
                        'parameters' => [
                            'organisation'       => $palletReturn->organisation->slug,
                            'fulfilment'         => $palletReturn->fulfilment->slug,
                            'fulfilmentCustomer' => $palletReturn->fulfilmentCustomer->id,
                            'palletReturn'       => $palletReturn->reference
                        ]
                    ]
                ],

                'uploadRoutes' => [
                    'history' => [
                        'name'       => 'grp.org.fulfilments.show.crm.customers.show.pallet-returns.pallets.uploads.history',
                        'parameters' => [
                            'organisation'       => $palletReturn->organisation->slug,
                            'fulfilment'         => $palletReturn->fulfilment->slug,
                            'fulfilmentCustomer' => $palletReturn->fulfilmentCustomer->id,
                            'palletReturn'       => $palletReturn->reference
                        ]
                    ],
                    'download' => [
                        'name'       => 'grp.org.fulfilments.show.crm.customers.show.pallet-returns.pallets.uploads.templates',
                        'parameters' => [
                            'organisation'       => $palletReturn->organisation->slug,
                            'fulfilment'         => $palletReturn->fulfilment->slug,
                            'fulfilmentCustomer' => $palletReturn->fulfilmentCustomer->slug,
                            'palletReturn'       => $palletReturn->reference
                        ]
                    ],
                ],

                'palletRoute' => [
                    'index' => [
                        'name'       => 'retina.storage.stored-pallets.index',
                        'parameters' => []
                    ],
                    'store' => [
                        'name'       => 'retina.models.pallet-return.pallet.store',
                        'parameters' => [
                            'palletReturn'       => $palletReturn->id
                        ]
                    ]
                ],

                'tabs' => [
                    'current'    => $this->tab,
                    'navigation' => PalletReturnTabsEnum::navigation()
                ],

                'data' => PalletReturnResource::make($palletReturn),

                PalletReturnTabsEnum::PALLETS->value => $this->tab == PalletReturnTabsEnum::PALLETS->value ?
                    fn () => PalletsResource::collection(IndexPallets::run($palletReturn))
                    : Inertia::lazy(fn () => PalletsResource::collection(IndexPallets::run($palletReturn))),
            ]
        )->table(
            IndexPallets::make()->tableStructure(
                $palletReturn,
                prefix: PalletReturnTabsEnum::PALLETS->value
            )
        );
    }


    public function jsonResponse(PalletReturn $palletReturn): PalletReturnsResource
    {
        return new PalletReturnsResource($palletReturn);
    }

    public function getBreadcrumbs(string $routeName, array $routeParameters, $suffix = ''): array
    {
        $headCrumb = function (PalletReturn $palletReturn, array $routeParameters, string $suffix) {
            return [
                [
                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => $routeParameters['index'],
                            'label' => __('pallet returns')
                        ],
                        'model' => [
                            'route' => $routeParameters['model'],
                            'label' => $palletReturn->reference,
                        ],

                    ],
                    'suffix' => $suffix
                ],
            ];
        };

        $palletReturn = PalletReturn::where('reference', $routeParameters['palletReturn'])->first();

        return match ($routeName) {
            'retina.storage.pallet-returns.show' => array_merge(
                ShowStorageDashboard::make()->getBreadcrumbs(),
                $headCrumb(
                    $palletReturn,
                    [
                        'index' => [
                            'name'       => 'retina.storage.pallet-returns.index',
                            'parameters' => []
                        ],
                        'model' => [
                            'name'       => 'retina.storage.pallet-returns.show',
                            'parameters' => $routeParameters
                        ]
                    ],
                    $suffix
                )
            ),

            default => []
        };
    }

    public function getPrevious(PalletReturn $palletReturn, ActionRequest $request): ?array
    {
        $previous = PalletReturn::where('id', '<', $palletReturn->id)->orderBy('id', 'desc')->first();

        return $this->getNavigation($previous, $request->route()->getName());
    }

    public function getNext(PalletReturn $palletReturn, ActionRequest $request): ?array
    {
        $next = PalletReturn::where('id', '>', $palletReturn->id)->orderBy('id')->first();

        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?PalletReturn $palletReturn, string $routeName): ?array
    {
        if (!$palletReturn) {
            return null;
        }


        return match (class_basename($this->parent)) {
            'Warehouse' => [
                'label' => $palletReturn->reference,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'organisation'   => $palletReturn->organisation->slug,
                        'warehouse'      => $palletReturn->warehouse->slug,
                        'palletReturn'   => $palletReturn->reference
                    ]

                ]
            ],
            'FulfilmentCustomer' => [
                'label' => $palletReturn->reference,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'organisation'       => $palletReturn->organisation->slug,
                        'fulfilment'         => $palletReturn->fulfilment->slug,
                        'fulfilmentCustomer' => $palletReturn->fulfilmentCustomer->slug,
                        'palletReturn'       => $palletReturn->reference
                    ]

                ]
            ],
            default => []
        };
    }
}
