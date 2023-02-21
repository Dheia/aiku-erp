<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Mon, 29 Aug 2022 17:12:26 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia F
 */

namespace App\Services\Tenant\Aurora;

use App\Actions\SourceFetch\Aurora\FetchCustomerClients;
use App\Models\Helpers\Address;
use Illuminate\Support\Facades\DB;

class FetchAuroraOrder extends FetchAurora
{
    protected function parseModel(): void
    {
        $deliveryData = [];

        if ($this->auroraModelData->{'Order For Collection'} == 'Yes') {
            $deliveryData['collection'] = true;
        } else {
            if ($this->auroraModelData->{'Order Email'}) {
                $deliveryData['email'] = $this->auroraModelData->{'Order Email'};
            }
            if ($this->auroraModelData->{'Order Telephone'}) {
                $deliveryData['phone'] = $this->auroraModelData->{'Order Telephone'};
            }
        }

        if ($this->auroraModelData->{'Order State'} == "InBasket") {
            return;
        }

        if ($this->auroraModelData->{'Order Customer Client Key'} != "") {
            $parent = FetchCustomerClients::run(
                $this->tenantSource,
                $this->auroraModelData->{'Order Customer Client Key'},
            );
        } else {
            $parent = $this->parseCustomer(
                $this->auroraModelData->{'Order Customer Key'}
            );
        }

        $this->parsedData["parent"] = $parent;
        if (!$parent) {
            return;
        }

        $state = match ($this->auroraModelData->{'Order State'}) {
            "InWarehouse", "Packed" => "in-warehouse",
            "PackedDone" => "packed",
            "Approved" => "finalised",
            "Dispatched" => "dispatched",
            default => "submitted",
        };


        $data = [
            "delivery_data" => $deliveryData
        ];

        $cancelled_at = null;
        if ($this->auroraModelData->{'Order State'} == "Cancelled") {
            $cancelled_at = $this->auroraModelData->{'Order Cancelled Date'};
            if (!$cancelled_at) {
                $cancelled_at = $this->auroraModelData->{'Order Date'};
            }

            if (
                $this->auroraModelData->{'Order Invoiced Date'} != "" or
                $this->auroraModelData->{'Order Dispatched Date'} != ""
            ) {
                $stateWhenCancelled = "finalised";
            } elseif (
                $this->auroraModelData->{'Order Packed Date'} != "" or
                $this->auroraModelData->{'Order Packed Done Date'} != ""
            ) {
                $stateWhenCancelled = "packed";
            } elseif (
                $this->auroraModelData->{'Order Send to Warehouse Date'} != ""
            ) {
                $stateWhenCancelled = "in-warehouse";
            } else {
                $stateWhenCancelled = "submitted";
            }

            $data['cancelled'] = [
                'state' => $stateWhenCancelled
            ];
        }




        $this->parsedData["order"] = [
            'date'            => $this->auroraModelData->{'Order Date'},
            'submitted_at'    => $this->parseDate($this->auroraModelData->{'Order Submitted by Customer Date'}),
            'in_warehouse_at' => $this->parseDate($this->auroraModelData->{'Order Send to Warehouse Date'}),
            'packed_at'       => $this->parseDate($this->auroraModelData->{'Order Packed Date'}),
            'finalised_at'    => $this->parseDate($this->auroraModelData->{'Order Packed Done Date'}),
            'dispatched_at'   => $this->parseDate($this->auroraModelData->{'Order Dispatched Date'}),


            "number"          => $this->auroraModelData->{'Order Public ID'},
            'customer_number' => $this->auroraModelData->{'Order Customer Purchase Order ID'},
            "state"           => $state,
            "source_id"       => $this->auroraModelData->{'Order Key'},
            "exchange"        => $this->auroraModelData->{'Order Currency Exchange'},
            "created_at"      => $this->auroraModelData->{'Order Created Date'},
            "cancelled_at"    => $cancelled_at,
            "data"            => $data
        ];

        $deliveryAddressData                  = $this->parseAddress(
            prefix:        "Order Delivery",
            auAddressData: $this->auroraModelData,
        );
        $this->parsedData["delivery_address"] = new Address(
            $deliveryAddressData,
        );

        $billingAddressData                  = $this->parseAddress(
            prefix:        "Order Invoice",
            auAddressData: $this->auroraModelData,
        );
        $this->parsedData["billing_address"] = new Address($billingAddressData);
    }

    protected function fetchData($id): object|null
    {
        return DB::connection("aurora")
            ->table("Order Dimension")
            ->where("Order Key", $id)
            ->first();
    }
}
