<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Wed, 15 Mar 2023 11:34:34 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Inventory\Location\UI;

use App\Actions\Helpers\History\IndexHistory;
use App\Actions\Inventory\Warehouse\UI\ShowWarehouse;
use App\Actions\Inventory\WarehouseArea\UI\ShowWarehouseArea;
use App\Actions\OrgAction;
use App\Actions\Traits\Actions\WithActionButtons;
use App\Enums\UI\LocationTabsEnum;
use App\Http\Resources\History\HistoryResource;
use App\Models\Inventory\Location;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseArea;
use App\Models\SysAdmin\Organisation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;

class ShowLocation extends OrgAction
{
    use WithActionButtons;

    private WarehouseArea|Warehouse $parent;

    public function handle(Location $location): Location
    {
        return $location;
    }

    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit   = $request->user()->hasPermissionTo("inventory.{$this->warehouse->id}.edit");
        $this->canDelete = $request->user()->hasPermissionTo("inventory.{$this->warehouse->id}.edit");

        return $request->user()->hasPermissionTo("inventory.{$this->warehouse->id}.view");
    }


    public function asController(Organisation $organisation, Warehouse $warehouse, WarehouseArea $warehouseArea, Location $location, ActionRequest $request): Location
    {
        $this->parent = $warehouseArea;
        $this->initialisationFromWarehouse($warehouse, $request);

        return $this->handle($location);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function inWarehouse(Organisation $organisation, Warehouse $warehouse, Location $location, ActionRequest $request): Location
    {
        $this->parent = $warehouse;
        $this->initialisationFromWarehouse($warehouse, $request);

        return $this->handle($location);
    }


    public function htmlResponse(Location $location, ActionRequest $request): Response
    {
        if ($this->parent instanceof Warehouse) {
            $container = [
                'icon'    => ['fal', 'fa-warehouse'],
                'tooltip' => __('Warehouse'),
                'label'   => Str::possessive($this->parent->code)
            ];
        } else {
            $container = [
                'icon'    => ['fal', 'fa-map-signs'],
                'tooltip' => __('Warehouse area'),
                'label'   => Str::possessive($this->parent->code)
            ];
        }


        return Inertia::render(
            'Org/Warehouse/Location',
            [
                'title'       => __('location'),
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->originalParameters()
                ),
                'navigation'  => [
                    'previous' => $this->getPrevious($location, $request),
                    'next'     => $this->getNext($location, $request),
                ],
                'pageHead'    => [
                    'container' => $container,
                    'icon'      => [
                        'title' => __('locations'),
                        'icon'  => 'fal fa-inventory'
                    ],
                    'title'     => $location->slug,
                    'actions'   => [
                        $this->canDelete ? $this->getDeleteActionIcon($request) : null,
                        $this->canEdit ? $this->getEditActionIcon($request) : null,
                    ],
                ],
                'tabs'        => [
                    'current'    => $this->tab,
                    'navigation' => LocationTabsEnum::navigation()

                ],

                LocationTabsEnum::SHOWCASE->value => $this->tab == LocationTabsEnum::SHOWCASE->value ?
                    fn () => GetLocationShowcase::run($location)
                    : Inertia::lazy(fn () => GetLocationShowcase::run($location)),

                LocationTabsEnum::HISTORY->value => $this->tab == LocationTabsEnum::HISTORY->value ?
                    fn () => HistoryResource::collection(IndexHistory::run($location))
                    : Inertia::lazy(fn () => HistoryResource::collection(IndexHistory::run($location)))
            ]
        )->table(IndexHistory::make()->tableStructure());
    }


    public function jsonResponse(Location $location): JsonResource
    {
        return new JsonResource($location);
    }

    public function getBreadcrumbs(string $routeName, array $routeParameters, string $suffix = ''): array
    {
        $headCrumb = function (Location $location, array $routeParameters, string $suffix) {
            return [
                [
                    'type'           => 'modelWithIndex',
                    'modelWithIndex' => [
                        'index' => [
                            'route' => $routeParameters['index'],
                            'label' => __('locations')
                        ],
                        'model' => [
                            'route' => $routeParameters['model'],
                            'label' => $location->code,
                        ],

                    ],
                    'suffix'         => $suffix
                ],
            ];
        };

        $location = Location::where('slug', $routeParameters['location'])->first();

        return match ($routeName) {
            'grp.org.warehouses.show.infrastructure.locations.show' => array_merge(
                (new ShowWarehouse())->getBreadcrumbs(Arr::only($routeParameters, ['organisation', 'warehouse'])),
                $headCrumb(
                    $location,
                    [
                        'index' => [
                            'name'       => 'grp.org.warehouses.show.infrastructure.locations.index',
                            'parameters' => Arr::only($routeParameters, ['organisation', 'warehouse'])
                        ],
                        'model' => [
                            'name'       => 'grp.org.warehouses.show.infrastructure.locations.show',
                            'parameters' => Arr::only($routeParameters, ['organisation', 'warehouse', 'location'])
                        ]
                    ],
                    $suffix
                )
            ),
            'grp.org.warehouses.show.infrastructure.warehouse-areas.show.locations.show' => array_merge(
                (new ShowWarehouseArea())->getBreadcrumbs(
                    Arr::only($routeParameters, ['organisation', 'warehouse', 'warehouseArea'])
                ),
                $headCrumb(
                    $location,
                    [
                        'index' => [
                            'name'       => 'grp.org.warehouses.show.infrastructure.warehouse-areas.show.locations.index',
                            'parameters' => Arr::only($routeParameters, ['organisation', 'warehouse', 'warehouseArea'])
                        ],
                        'model' => [
                            'name'       => 'grp.org.warehouses.show.infrastructure.warehouse-areas.show.locations.show',
                            'parameters' => Arr::only($routeParameters, ['organisation', 'warehouse', 'warehouseArea', 'location'])
                        ]
                    ],
                    $suffix
                ),
            ),

            default => []
        };
    }

    public function getPrevious(Location $location, ActionRequest $request): ?array
    {
        $previous = Location::where('slug', '<', $location->slug)->when(true, function ($query) use ($location, $request) {
            if ($this->parent instanceof Warehouse) {
                $query->where('locations.warehouse_id', $location->warehouse_id);
            } else {
                $query->where('locations.warehouse_area_id', $location->warehouse_area_id);
            }
        })->orderBy('slug', 'desc')->first();

        return $this->getNavigation($previous, $request->route()->getName());
    }

    public function getNext(Location $location, ActionRequest $request): ?array
    {
        $next = Location::where('slug', '>', $location->slug)->when(true, function ($query) use ($location, $request) {
            if ($this->parent instanceof Warehouse) {
                $query->where('locations.warehouse_id', $location->warehouse_id);
            } else {
                $query->where('locations.warehouse_area_id', $location->warehouse_area_id);
            }
        })->orderBy('slug')->first();

        return $this->getNavigation($next, $request->route()->getName());
    }

    private function getNavigation(?Location $location, string $routeName): ?array
    {
        if (!$location) {
            return null;
        }

        if ($this->parent instanceof Warehouse) {
            return [
                'label' => $location->slug,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'organisation' => $location->organisation->slug,
                        'warehouse'    => $location->warehouse->slug,
                        'location'     => $location->slug
                    ]

                ]
            ];
        } else {
            return [
                'label' => $location->slug,
                'route' => [
                    'name'       => $routeName,
                    'parameters' => [
                        'organisation'  => $location->organisation->slug,
                        'warehouse'     => $location->warehouse->slug,
                        'warehouseArea' => $location->warehouseArea->slug,
                        'location'      => $location->slug
                    ]
                ]
            ];
        }
    }

}
