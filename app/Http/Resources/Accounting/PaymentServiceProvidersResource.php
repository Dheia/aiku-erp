<?php
/*
 *  Author: Jonathan lopez <raul@inikoo.com>
 *  Created: Sat, 22 Oct 2022 18:53:15 British Summer Time, Sheffield, UK
 *  Copyright (c) 2022, inikoo
 */

namespace App\Http\Resources\Accounting;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $number_payments
 * @property integer $number_payment_accounts
 * @property string $slug
 * @property string $code
 * @property mixed $created_at
 * @property string $name
 *
 */
class PaymentServiceProvidersResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'number_payments'         => $this->number_payments,
            'number_payment_accounts' => $this->number_payment_accounts,
            'slug'                    => $this->slug,
            'code'                    => $this->code,
            'name'                    => $this->name,
            'created_at'              => $this->created_at,
        ];
    }
}
