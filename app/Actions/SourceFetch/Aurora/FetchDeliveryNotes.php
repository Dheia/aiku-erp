<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 31 Jan 2023 20:16:44 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;

use App\Actions\Dispatch\DeliveryNote\StoreDeliveryNote;
use App\Actions\Dispatch\DeliveryNote\UpdateDeliveryNote;
use App\Actions\Dispatch\Shipment\StoreShipment;
use App\Actions\Dispatch\Shipment\UpdateShipment;
use App\Actions\Helpers\Address\AttachHistoricAddressToModel;
use App\Actions\Helpers\Address\StoreHistoricAddress;
use App\Actions\Helpers\Address\UpdateHistoricAddressToModel;
use App\Models\Dispatch\DeliveryNote;
use App\Models\Dispatch\Shipment;
use App\Services\Tenant\SourceTenantService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;

class FetchDeliveryNotes extends FetchAction
{
    public string $commandSignature = 'fetch:delivery-notes {tenants?*} {--s|source_id=}  {--N|only_new : Fetch only new}';

    #[NoReturn] public function handle(SourceTenantService $tenantSource, int $tenantSourceId): ?DeliveryNote
    {
        if ($deliveryNoteData = $tenantSource->fetchDeliveryNote($tenantSourceId)) {
            if (!empty($deliveryNoteData['delivery_note']['source_id']) and $deliveryNote = DeliveryNote::withTrashed()->where('source_id', $deliveryNoteData['delivery_note']['source_id'])
                    ->first()) {
                UpdateDeliveryNote::run($deliveryNote, $deliveryNoteData['delivery_note']);
                if ($currentDeliveryAddress = $deliveryNote->getAddress('delivery')) {
                    if ($currentDeliveryAddress->checksum != $deliveryNoteData['delivery_address']->getChecksum()) {
                        $deliveryAddress = StoreHistoricAddress::run($deliveryNoteData['delivery_address']);
                        UpdateHistoricAddressToModel::run($deliveryNote, $currentDeliveryAddress, $deliveryAddress, ['scope' => 'delivery']);
                    }
                } else {
                    $deliveryAddress = StoreHistoricAddress::run($deliveryNoteData['delivery_address']);
                    AttachHistoricAddressToModel::run($deliveryNote, $deliveryAddress, ['scope' => 'delivery']);
                }

                $this->updateAurora($deliveryNote);

                return $deliveryNote;
            } else {
                if ($deliveryNoteData['order']) {
                    $deliveryNote = StoreDeliveryNote::run(
                        $deliveryNoteData['order'],
                        $deliveryNoteData['delivery_note'],
                        $deliveryNoteData['delivery_address']
                    );
                    $this->fetchDeliveryNoteTransactions($tenantSource, $deliveryNote);

                    $this->updateAurora($deliveryNote);


                    if ($deliveryNoteData['shipment'] and !Arr::get($deliveryNoteData['order'], 'data.delivery_data.collection')) {
                        if ($shipment = Shipment::withTrashed()->where('source_id', $deliveryNoteData['shipment']['source_id'])->first()) {
                            UpdateShipment::run($shipment, $deliveryNoteData['shipment']);
                        } else {
                            StoreShipment::run($deliveryNote, $deliveryNoteData['shipment']);
                        }
                    }


                    return $deliveryNote;
                }
                print "Warning delivery note $tenantSourceId do not have order\n";
            }
        } else {
            print "Warning error fetching delivery note $tenantSourceId\n";
        }

        return null;
    }

    private function fetchDeliveryNoteTransactions($tenantSource, $deliveryNote): void
    {
        $transactionsToDelete = $deliveryNote->deliveryNoteItems()->pluck('source_id', 'id')->all();


        foreach (
            DB::connection('aurora')
                ->table('Inventory Transaction Fact')
                ->select('Inventory Transaction Key')
                ->where('Delivery Note Key', $deliveryNote->source_id)
                ->get() as $auroraData
        ) {
            $transactionsToDelete = array_diff($transactionsToDelete, [$auroraData->{'Inventory Transaction Key'}]);
            FetchDeliveryNoteTransactions::run($tenantSource, $auroraData->{'Inventory Transaction Key'}, $deliveryNote);
        }
        $deliveryNote->deliveryNoteItems()->whereIn('id', array_keys($transactionsToDelete))->delete();
    }

    public function updateAurora(DeliveryNote $deliveryNote)
    {
        DB::connection('aurora')->table('Delivery Note Dimension')
            ->where('Delivery Note Key', $deliveryNote->source_id)
            ->update(['aiku_id' => $deliveryNote->id]);
    }

    public function getModelsQuery(): Builder
    {
        $query = DB::connection('aurora')
            ->table('Delivery Note Dimension')
            ->select('Delivery Note Key as source_id');
        if ($this->onlyNew) {
            $query->whereNull('aiku_id');
        }
        $query->orderBy('Delivery Note Date');

        return $query;
    }

    public function count(): ?int
    {
        $query = DB::connection('aurora')->table('Delivery Note Dimension');
        if ($this->onlyNew) {
            $query->whereNull('aiku_id');
        }

        return $query->count();
    }
}
