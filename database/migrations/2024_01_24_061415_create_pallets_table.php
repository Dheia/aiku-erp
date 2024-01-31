<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 24 Jan 2024 16:25:29 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

use App\Enums\Fulfilment\Pallet\PalletTypeEnum;
use App\Enums\Fulfilment\Pallet\PalletStateEnum;
use App\Enums\Fulfilment\Pallet\PalletStatusEnum;
use App\Stubs\Migrations\HasGroupOrganisationRelationship;
use App\Stubs\Migrations\HasSoftDeletes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    use HasGroupOrganisationRelationship;
    use HasSoftDeletes;
    public function up(): void
    {
        Schema::create('pallets', function (Blueprint $table) {
            $table->increments('id');
            $table = $this->groupOrgRelationship($table);
            $table->string('slug')->unique()->collation('und_ns');
            $table->string('customer_reference')->nullable()->index()->collation('und_ci');
            $table->unsignedInteger('fulfilment_id')->index();
            $table->foreign('fulfilment_id')->references('id')->on('fulfilments');
            $table->unsignedInteger('fulfilment_customer_id')->index();
            $table->foreign('fulfilment_customer_id')->references('id')->on('fulfilment_customers');
            $table->unsignedInteger('warehouse_id')->index();
            $table->foreign('warehouse_id')->references('id')->on('warehouses');

            $table->unsignedInteger('warehouse_area_id')->nullable()->index();
            $table->foreign('warehouse_area_id')->references('id')->on('warehouse_areas');

            $table->unsignedInteger('location_id')->index()->nullable();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->string('status')->index()->default(PalletStatusEnum::IN_PROCESS->value);
            $table->string('state')->index()->default(PalletStateEnum::IN_PROCESS->value);
            $table->string('type')->index()->default(PalletTypeEnum::PALLET->value);
            $table->string('notes', 1024);
            $table->decimal('items_quantity')->default(0);
            $table->dateTimeTz('received_at')->nullable();
            $table->dateTimeTz('booked_in_at')->nullable();
            $table->dateTimeTz('settled_at')->nullable();
            $table->jsonb('data');
            $table->timestampsTz();
            $table = $this->softDeletes($table);
            $table->string('source_id')->nullable()->unique();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('pallets');
    }
};
