<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 20 Jun 2023 20:33:12 Malaysia Time, Pantai Lembeng, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\OMS\Order\Traits;

use App\Actions\Market\Shop\Hydrators\ShopHydrateOrders;
use App\Actions\OMS\Order\HydrateOrder;
use App\Actions\Organisation\Organisation\Hydrators\OrganisationHydrateOrders;
use App\Models\OMS\Order;

trait HasHydrators
{
    public function orderHydrators(Order $order): void
    {
        HydrateOrder::make()->originalItems($order);
        OrganisationHydrateOrders::run($order->shop->organisation);

        if($order->customer) {
            $parent = $order->customer;
        } else {
            $parent = $order->customerClient;
        }

        ShopHydrateOrders::run($parent->shop);
    }
}
