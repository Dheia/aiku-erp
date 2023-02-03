<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 31 Jan 2023 20:16:44 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;


use App\Actions\Delivery\DeliveryNoteItem\StoreDeliveryNoteItem;
use App\Models\Delivery\DeliveryNote;
use App\Models\Delivery\DeliveryNoteItem;
use App\Services\Tenant\SourceTenantService;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchDeliveryNoteTransactions
{
    use AsAction;


    #[NoReturn] public function handle(SourceTenantService $tenantSource, int $source_id, DeliveryNote $deliveryNote): ?DeliveryNoteItem
    {
        if ($transactionData = $tenantSource->fetchDeliveryNoteTransaction(id: $source_id, deliveryNote: $deliveryNote)) {
            if (!DeliveryNoteItem::where('source_id', $transactionData['delivery_note_item']['source_id'])
                ->first()) {
                $transaction = StoreDeliveryNoteItem::run(
                    deliveryNote: $deliveryNote,
                    modelData:    $transactionData['delivery_note_item']
                );

                DB::connection('aurora')->table('Inventory Transaction Fact')
                    ->where('Inventory Transaction Key', $transaction->source_id)
                    ->update(['aiku_id' => $transaction->id]);

                return $transaction;
            }
        }


        return null;
    }


}
