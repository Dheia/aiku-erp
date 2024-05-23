<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 25 Jan 2024 15:20:59 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

use App\Enums\Fulfilment\PalletDelivery\PalletDeliveryStateEnum;
use App\Stubs\Migrations\HasFulfilmentDelivery;
use App\Stubs\Migrations\HasGroupOrganisationRelationship;
use App\Stubs\Migrations\HasSoftDeletes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    use HasGroupOrganisationRelationship;
    use HasSoftDeletes;
    use HasFulfilmentDelivery;

    public function up(): void
    {
        Schema::create('pallet_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table = $this->getPalletIOFields($table);

            $table->string('state')->default(PalletDeliveryStateEnum::IN_PROCESS->value);

            foreach (PalletDeliveryStateEnum::cases() as $state) {
                $table->dateTimeTz("{$state->snake()}_at")->nullable();
            }
            $table->date('estimated_delivery_date')->nullable();
            $table->dateTimeTz('date')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('public_notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->jsonb('data')->nullable();
            $table->timestampsTz();
            $this->softDeletes($table);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('pallet_deliveries');
    }
};
