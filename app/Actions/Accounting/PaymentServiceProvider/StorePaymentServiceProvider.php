<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 27 Feb 2023 11:19:37 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Accounting\PaymentServiceProvider;

use App\Actions\OrgAction;
use App\Enums\Accounting\PaymentServiceProvider\PaymentServiceProviderTypeEnum;
use App\Models\Accounting\PaymentServiceProvider;
use App\Models\SysAdmin\Organisation;
use App\Rules\IUnique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\WithAttributes;

class StorePaymentServiceProvider extends OrgAction
{
    use AsAction;
    use WithAttributes;

    private bool $asAction = false;

    public function handle(Organisation $organisation, array $modelData): PaymentServiceProvider
    {
        data_set($modelData, 'group_id', $organisation->group_id);
        /** @var PaymentServiceProvider $paymentServiceProvider */
        $paymentServiceProvider = $organisation->paymentServiceProviders()->create($modelData);
        $paymentServiceProvider->stats()->create();

        return $paymentServiceProvider;
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("accounting.edit");
    }

    public function rules(): array
    {
        return [
            'code'      => [
                'required',
                'between:2,16',
                'alpha_dash',
                new IUnique(
                    table: 'payment_service_providers',
                    extraConditions: [
                        ['column' => 'group_id', 'value' => $this->organisation->group_id],
                    ]
                ),
            ],
            'type'      => ['required', Rule::in(PaymentServiceProviderTypeEnum::values())],
            'source_id' => ['sometimes', 'string'],
        ];
    }

    public function action(Organisation $organisation, array $modelData): PaymentServiceProvider
    {
        $this->asAction = true;
        $this->initialisation($organisation, $modelData);

        return $this->handle($organisation, $this->validatedData);
    }

    public function asController(Organisation $organisation, ActionRequest $request): PaymentServiceProvider
    {
        $this->initialisation($organisation, $request);

        return $this->handle($organisation, $this->validatedData);
    }

    public function htmlResponse(PaymentServiceProvider $paymentServiceProvider): RedirectResponse
    {
        return Redirect::route('grp.accounting.payment-service-providers.show', $paymentServiceProvider->slug);
    }
}
