<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 26 Feb 2024 19:57:44 Central Standard Time, Mexico City, Mexico
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Actions\Fulfilment\FulfilmentCustomer\UI;

use App\Enums\Fulfilment\Pallet\PalletStateEnum;
use App\Enums\Fulfilment\PalletDelivery\PalletDeliveryStateEnum;
use App\Enums\Fulfilment\PalletReturn\PalletReturnStateEnum;
use App\Http\Resources\Fulfilment\FulfilmentCustomerResource;
use App\Http\Resources\Sales\CustomersResource;
use App\Models\Fulfilment\FulfilmentCustomer;
use Lorisleiva\Actions\Concerns\AsObject;

class GetFulfilmentCustomerShowcase
{
    use AsObject;

    public function handle(FulfilmentCustomer $fulfilmentCustomer): array
    {
        return [
            'customer'            => CustomersResource::make($fulfilmentCustomer->customer)->getArray(),
            'fulfilment_customer' => FulfilmentCustomerResource::make($fulfilmentCustomer)->getArray(),
            'updateRoute'         => [
                'name'       => 'grp.models.fulfilment-customer.update',
                'parameters' => [$fulfilmentCustomer->id]
            ],
            'pieData'               => $this->getDashboardData($fulfilmentCustomer),
            'webhook_access_key'    => $fulfilmentCustomer->webhook_access_key
        ];
    }

    public function getDashboardData(FulfilmentCustomer $parent): array
    {
        $stats = [];

        $stats['pallets'] = [
            'label' => __('Pallet'),
            'count' => $parent->number_pallets
        ];

        foreach (PalletStateEnum::cases() as $case) {
            $stats['pallets']['cases'][$case->value] = [
                'value' => $case->value,
                'icon'  => PalletStateEnum::stateIcon()[$case->value],
                'count' => PalletStateEnum::count($parent)[$case->value],
                'label' => PalletStateEnum::labels()[$case->value]
            ];
        }

        $stats['pallet_delivery'] = [
            'label' => __('Pallet Delivery'),
            'count' => $parent->number_pallet_deliveries
        ];
        foreach (PalletDeliveryStateEnum::cases() as $case) {
            $stats['pallet_delivery']['cases'][$case->value] = [
                'value' => $case->value,
                'icon'  => PalletDeliveryStateEnum::stateIcon()[$case->value],
                'count' => PalletDeliveryStateEnum::count($parent)[$case->value],
                'label' => PalletDeliveryStateEnum::labels()[$case->value]
            ];
        }

        $stats['pallet_return'] = [
            'label' => __('Pallet Return'),
            'count' => $parent->number_pallet_returns
        ];
        foreach (PalletReturnStateEnum::cases() as $case) {
            $stats['pallet_return']['cases'][$case->value] = [
                'value' => $case->value,
                'icon'  => PalletReturnStateEnum::stateIcon()[$case->value],
                'count' => PalletReturnStateEnum::count($parent)[$case->value],
                'label' => PalletReturnStateEnum::labels()[$case->value]
            ];
        }

        return $stats;
    }
}
