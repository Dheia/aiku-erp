<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Thu, 16 Mar 2023 08:17:51 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Accounting\Payment\UI;

use App\Actions\Accounting\PaymentAccount\UI\ShowPaymentAccount;
use App\Actions\Accounting\PaymentServiceProvider\UI\ShowPaymentServiceProvider;
use App\Actions\OrgAction;
use App\Actions\UI\Accounting\ShowAccountingDashboard;
use App\Http\Resources\Accounting\PaymentsResource;
use App\InertiaTable\InertiaTable;
use App\Models\Accounting\Payment;
use App\Models\Accounting\PaymentAccount;
use App\Models\Accounting\PaymentServiceProvider;
use App\Models\Market\Shop;
use App\Models\SysAdmin\Organisation;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\ActionRequest;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\QueryBuilder;

class IndexPayments extends OrgAction
{
    private Organisation|PaymentAccount|Shop|PaymentServiceProvider $parent;

    public function handle($parent, $prefix = null): LengthAwarePaginator
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->whereStartWith('payment.reference', $value);
            });
        });

        if ($prefix) {
            InertiaTable::updateQueryBuilderParameters($prefix);
        }

        $queryBuilder = QueryBuilder::for(Payment::class);


        if (class_basename($parent) == 'PaymentServiceProvider') {
            $queryBuilder->where('payment_accounts.payment_service_provider_id', $parent->id);
        } elseif (class_basename($parent) == 'PaymentAccount') {
            $queryBuilder->where('payments.payment_account_id', $parent->id);
        } elseif (class_basename($parent) == 'Shop') {
            $queryBuilder->where('payments.shop_id', $parent->id);
        } elseif (class_basename($parent) == 'Order') {
            $queryBuilder->leftJoin(
                'paymentables',
                function ($leftJoin) {
                    $leftJoin->on('paymentables.payment_id', 'payments.id');
                    $leftJoin->on(DB::raw('paymentables.paymentable_type'), DB::raw("'Order'"));
                }
            );
            $queryBuilder->where('paymentables.paymentable_id', $parent->id);
        }

        /*
        foreach ($this->elementGroups as $key => $elementGroup) {
            $queryBuilder->whereElementGroup(
                key: $key,
                allowedElements: array_keys($elementGroup['elements']),
                engine: $elementGroup['engine'],
                prefix: $prefix
            );
        }
        */

        return $queryBuilder
            ->defaultSort('-date')
            ->select([
                'payments.reference',
                'payments.slug',
                'payments.status',
                'payments.date',
                'payment_accounts.slug as payment_accounts_slug',
                'payment_service_providers.slug as payment_service_providers_slug'
            ])
            ->leftJoin('payment_accounts', 'payments.payment_account_id', 'payment_accounts.id')
            ->leftJoin('payment_service_providers', 'payment_accounts.payment_service_provider_id', 'payment_service_providers.id')
            ->when($parent, function ($query) use ($parent) {
            })
            ->allowedSorts(['reference', 'status', 'date'])
            ->allowedFilters([$globalSearch])
            ->withPaginator($prefix)
            ->withQueryString();
    }

    public function tableStructure(Organisation|PaymentServiceProvider|PaymentAccount $parent, ?array $modelOperations = null, $prefix = null): Closure
    {
        return function (InertiaTable $table) use ($modelOperations, $prefix, $parent) {
            if ($prefix) {
                $table
                    ->name($prefix)
                    ->pageName($prefix.'Page');
            }
            $table
                ->withGlobalSearch()
                ->withModelOperations($modelOperations)
                ->defaultSort('-date')
                ->column(key: 'reference', label: __('reference'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'status', label: __('status'), canBeHidden: false, sortable: true, searchable: true)
                ->column(key: 'date', label: __('date'), canBeHidden: false, sortable: true, searchable: true);
        };
    }

    public function authorize(ActionRequest $request): bool
    {
        $this->canEdit = $request->user()->hasPermissionTo("accounting.{$this->organisation->id}.edit");

        return $request->user()->hasPermissionTo("accounting.{$this->organisation->id}.view");
    }


    public function inOrganisation(Organisation $organisation, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent=$organisation;
        $this->initialisation($organisation, $request);

        return $this->handle($organisation);
    }

    public function inPaymentServiceProvider(Organisation $organisation, PaymentServiceProvider $paymentServiceProvider, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent=$paymentServiceProvider;
        $this->initialisation($organisation, $request);

        return $this->handle($paymentServiceProvider);
    }

    /** @noinspection PhpUnused */
    public function inPaymentAccount(Organisation $organisation, PaymentAccount $paymentAccount, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent=$paymentAccount;
        $this->initialisation($organisation, $request);
        return $this->handle($paymentAccount);
    }


    /** @noinspection PhpUnusedParameterInspection */
    public function inPaymentAccountInPaymentServiceProvider(Organisation $organisation, PaymentServiceProvider $paymentServiceProvider, PaymentAccount $paymentAccount, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent=$paymentAccount;
        $this->initialisation($organisation, $request);
        return $this->handle($paymentAccount);
    }

    public function inShop(Organisation $organisation, Shop $shop, ActionRequest $request): LengthAwarePaginator
    {
        $this->parent=$shop;
        $this->initialisation($organisation, $request);
        return $this->handle($shop);
    }

    public function jsonResponse($payments): AnonymousResourceCollection
    {
        return PaymentsResource::collection($payments);
    }


    public function htmlResponse(LengthAwarePaginator $payments, ActionRequest $request): Response
    {
        $routeName       = $request->route()->getName();
        $routeParameters = $request->route()->originalParameters();

        return Inertia::render(
            'Accounting/Payments',
            [
                'breadcrumbs' => $this->getBreadcrumbs(
                    $routeName,
                    $routeParameters
                ),
                'title'       => __('payments '),
                'pageHead'    => [
                    'title'     => __('payments'),
                    'container' => match ($routeName) {
                        'grp.org.accounting.shops.show.payments.index' => [
                            'icon'    => ['fal', 'fa-store-alt'],
                            'tooltip' => __('Shop'),
                            'label'   => Str::possessive($routeParameters['shop']->name)
                        ],
                        default => null
                    },
                ],
                'data'        => PaymentsResource::collection($payments),


            ]
        )->table($this->tableStructure($this->parent));
    }


    public function getBreadcrumbs(string $routeName, array $routeParameters): array
    {
        $headCrumb = function () use ($routeName, $routeParameters) {
            return [
                [
                    'type'   => 'simple',
                    'simple' => [
                        'route' => [
                            'name'       => $routeName,
                            'parameters' => $routeParameters
                        ],
                        'label' => __('payments'),
                        'icon'  => 'fal fa-bars',

                    ],
                ],
            ];
        };

        return match ($routeName) {
            'grp.org.accounting.shops.show.payments.index' =>
            array_merge(
                ShowAccountingDashboard::make()->getBreadcrumbs('grp.org.accounting.shops.show.dashboard', $routeParameters),
                $headCrumb()
            ),
            'grp.org.accounting.payments.index' =>
            array_merge(
                ShowAccountingDashboard::make()->getBreadcrumbs('grp.org.accounting.dashboard', $routeParameters),
                $headCrumb()
            ),
            'grp.org.accounting.payment-service-providers.show.payments.index' =>
            array_merge(
                (new ShowPaymentServiceProvider())->getBreadcrumbs($routeParameters['paymentServiceProvider']),
                $headCrumb()
            ),
            'grp.org.accounting.payment-service-providers.show.payment-accounts.show.payments.index' =>
            array_merge(
                (new ShowPaymentAccount())->getBreadcrumbs('grp.org.accounting.payment-service-providers.show.payment-accounts.show', $routeParameters),
                $headCrumb()
            ),

            'grp.org.accounting.payment-accounts.show.payments.index' =>
            array_merge(
                (new ShowPaymentAccount())->getBreadcrumbs('grp.org.accounting.payment-accounts.show', $routeParameters),
                $headCrumb()
            ),

            default => []
        };
    }
}
