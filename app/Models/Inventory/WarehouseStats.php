<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Tue, 30 Aug 2022 12:12:37 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia F
 */

namespace App\Models\Inventory;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Inventory\WarehouseStats
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $number_warehouse_areas
 * @property int $number_locations
 * @property int $number_locations_state_operational
 * @property int $number_locations_state_broken
 * @property int $number_empty_locations
 * @property int $number_locations_no_stock_slots
 * @property string $stock_value
 * @property int $number_fulfilments
 * @property int $number_customers_interest_pallets_storage
 * @property int $number_customers_interest_items_storage
 * @property int $number_customers_interest_dropshipping
 * @property int $number_customers_with_stored_items
 * @property int $number_customers_with_pallets
 * @property int $number_customers_with_stored_items_state_in_process
 * @property int $number_customers_with_stored_items_state_received
 * @property int $number_customers_with_stored_items_state_booked_in
 * @property int $number_customers_with_stored_items_state_settled
 * @property int $number_customers_with_stored_items_status_in_process
 * @property int $number_customers_with_stored_items_status_storing
 * @property int $number_customers_with_stored_items_status_damaged
 * @property int $number_customers_with_stored_items_status_lost
 * @property int $number_customers_with_stored_items_status_returned
 * @property int $number_pallets
 * @property int $number_pallets_type_pallet
 * @property int $number_pallets_type_box
 * @property int $number_pallets_type_oversize
 * @property int $number_pallets_state_in_process
 * @property int $number_pallets_state_submitted
 * @property int $number_pallets_state_confirmed
 * @property int $number_pallets_state_received
 * @property int $number_pallets_state_not_received
 * @property int $number_pallets_state_booked_in
 * @property int $number_pallets_state_settled
 * @property int $number_pallets_status_in_process
 * @property int $number_pallets_status_storing
 * @property int $number_pallets_status_damaged
 * @property int $number_pallets_status_lost
 * @property int $number_pallets_status_returned
 * @property int $number_stored_items
 * @property int $number_stored_items_type_pallet
 * @property int $number_stored_items_type_box
 * @property int $number_stored_items_type_oversize
 * @property int $number_stored_items_state_in_process
 * @property int $number_stored_items_state_received
 * @property int $number_stored_items_state_booked_in
 * @property int $number_stored_items_state_settled
 * @property int $number_stored_items_status_in_process
 * @property int $number_stored_items_status_storing
 * @property int $number_stored_items_status_damaged
 * @property int $number_stored_items_status_lost
 * @property int $number_stored_items_status_returned
 * @property int $number_pallet_deliveries
 * @property int $number_pallet_deliveries_state_in_process
 * @property int $number_pallet_deliveries_state_submitted
 * @property int $number_pallet_deliveries_state_confirmed
 * @property int $number_pallet_deliveries_state_received
 * @property int $number_pallet_deliveries_state_not_received
 * @property int $number_pallet_deliveries_state_booked_in
 * @property int $number_pallet_returns
 * @property int $number_pallet_returns_state_in_process
 * @property int $number_pallet_returns_state_submitted
 * @property int $number_pallet_returns_state_confirmed
 * @property int $number_pallet_returns_state_picking
 * @property int $number_pallet_returns_state_picked
 * @property int $number_pallet_returns_state_dispatched
 * @property int $number_pallet_returns_state_cancel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Inventory\Warehouse $warehouse
 * @method static Builder|WarehouseStats newModelQuery()
 * @method static Builder|WarehouseStats newQuery()
 * @method static Builder|WarehouseStats query()
 * @mixin Eloquent
 */
class WarehouseStats extends Model
{
    protected $table = 'warehouse_stats';

    protected $guarded = [];



    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
