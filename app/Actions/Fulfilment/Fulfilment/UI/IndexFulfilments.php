<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 24 Jan 2024 14:52:40 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\Fulfilment\UI;

use App\Actions\OrgAction;
use App\Actions\UI\Dashboard\ShowDashboard;
use App\Enums\UI\FulfilmentTabsEnum;
use App\Http\Resources\Fulfilment\FulfilmentsResource;
use App\Http\Resources\Market\ShopResource;
use App\InertiaTable\InertiaTable;
use App\Models\Fulfilment\Fulfilment;
use App\Models\SysAdmin\Group;
use App\Models\SysAdmin\Organisation;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IndexFulfilments extends OrgAction
{
    private Organisation|Group $parent;

    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit = $request->user()->hasPermissionTo("fulfilments.{$this->organisation->id}.edit");

        return $request->user()->hasPermissionTo("fulfilments.{$this->organisation->id}.view");
    }

    public function asController(Organisation $organisation, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent = $organisation;
        $this->initialisation($organisation, $request)->withTab(FulfilmentTabsEnum::values());

        return $this->handle();
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function handle($prefix = null): LengthAwarePaginator
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->whereAnyWordStartWith('shops.name', $value)
                    ->orWhereStartWith('shops.code', $value);
            });
        });

        if ($prefix) {
            InertiaTable::updateQueryBuilderParameters($prefix);
        }

        $queryBuilder = QueryBuilder::for(Fulfilment::class);


        if (class_basename($this->parent) == 'Organisation') {
            $queryBuilder->where('fulfilments.organisation_id', $this->parent->id);
        } else {
            $queryBuilder->where('group_id', $this->parent->id);
        }

        return $queryBuilder
            ->defaultSort('shops.code')
            ->select(['code', 'name', 'fulfilments.slug as slug'])
            ->leftJoin('shops', 'shops.id', '=', 'fulfilments.shop_id')
            ->leftJoin('fulfilment_stats', 'fulfilment_stats.fulfilment_id', '=', 'fulfilments.id')
            ->allowedSorts(['code', 'name'])
            ->allowedFilters([$globalSearch])
            ->withPaginator($prefix)
            ->withQueryString();
    }

    public function tableStructure(Organisation|Group $parent, $prefix): Closure
    {
        return function (InertiaTable $table) use ($prefix, $parent) {
            if ($prefix) {
                $table
                    ->name($prefix)
                    ->pageName($prefix.'Page');
            }

            $table
                ->withGlobalSearch()
                ->withModelOperations()
                ->withEmptyState(
                    class_basename($parent) == 'Organisation' ?
                        [
                            'title' => __('No fulfilment stores found'),
                        ] : null
                )
                ->column(key: 'code', label: __('code'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'name', label: __('name'), canBeHidden: false, sortable: true, searchable: true)
                ->defaultSort('code');
        };
    }

    public function jsonResponse(): AnonymousResourceCollection
    {
        return ShopResource::collection($this->handle());
    }

    public function htmlResponse(LengthAwarePaginator $fulfilments, ActionRequest $request): Response
    {
        return Inertia::render(
            'Fulfilment/Fulfilments',
            [
                'breadcrumbs' => $this->getBreadcrumbs(
                    $request->route()->getName(),
                    $request->route()->originalParameters()
                ),
                'title'       => __('fulfilment shops'),
                'pageHead'    => [
                    'title'   => __('fulfilment shops'),
                    'icon'    => [
                        'icon'  => ['fal', 'fa-store-alt'],
                        'title' => __('fulfilment')
                    ],
                    'actions' => [
                        $this->canEdit ? [
                            'type'    => 'button',
                            'style'   => 'create',
                            'tooltip' => __('new fulfilment shop'),
                            'label'   => __('Fulfilment Shop'),
                            'route'   => [
                                'name'       => 'grp.org.fulfilments.create',
                                'parameters' => $request->route()->originalParameters()
                            ]
                        ] : false,
                    ]
                ],

                'tabs' => [
                    'current'    => $this->tab,
                    'navigation' => FulfilmentTabsEnum::navigation(),
                ],


                FulfilmentTabsEnum::FULFILMENTS->value => $this->tab == FulfilmentTabsEnum::FULFILMENTS->value ?
                    fn () => FulfilmentsResource::collection($fulfilments)
                    : Inertia::lazy(fn () => FulfilmentsResource::collection($fulfilments)),


            ]
        )->table($this->tableStructure(parent: $this->parent, prefix: FulfilmentTabsEnum::FULFILMENTS->value));
    }

    public function getBreadcrumbs(string $routeName, array $routeParameters, $suffix = null): array
    {
        if ($routeName == 'grp.org.shops.index') {
            return
                array_merge(
                    (new ShowDashboard())->getBreadcrumbs(),
                    [
                        [
                            'type'   => 'simple',
                            'simple' => [
                                'route' => [
                                    'name'       => 'grp.org.shops.index',
                                    'parameters' => $routeParameters
                                ],
                                'label' => __('shops'),
                                'icon'  => 'fal fa-bars'
                            ],
                            'suffix' => $suffix

                        ]
                    ]
                );
        }

        return [];
    }
}
