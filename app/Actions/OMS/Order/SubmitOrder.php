<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 20 Jun 2023 20:33:11 Malaysia Time, Pantai Lembeng, Bali, Id
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\OMS\Order;

use App\Actions\Market\Shop\Hydrators\ShopHydrateOrders;
use App\Actions\Organisation\Organisation\Hydrators\OrganisationHydrateOrders;
use App\Actions\Traits\WithActionUpdate;
use App\Http\Resources\Sales\OrderResource;
use App\Models\OMS\Order;
use Lorisleiva\Actions\Concerns\AsAction;

class SubmitOrder
{
    use WithActionUpdate;
    use AsAction;

    public function handle(Order $order): Order
    {
        $order = $this->update($order, [
            'state' => \App\Enums\OMS\Order\OrderStateEnum::SUBMITTED
        ]);

        OrganisationHydrateOrders::run(app('currentTenant'));
        ShopHydrateOrders::run($order->shop);

        return $order;
    }

    public function action(Order $order): Order
    {
        return $this->handle($order);
    }

    public function asController(Order $order): Order
    {
        return $this->handle($order);
    }

    public function jsonResponse(Order $order): OrderResource
    {
        return new OrderResource($order);
    }
}
