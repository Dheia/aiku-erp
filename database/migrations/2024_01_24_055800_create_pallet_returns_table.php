<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 15 Feb 2024 06:56:13 CTS, Mexico City, Mexico
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

use App\Enums\Fulfilment\PalletReturn\PalletReturnStateEnum;
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
        if(!Schema::hasTable('pallet_returns')) {
            Schema::create('pallet_returns', function (Blueprint $table) {
                $table->increments('id');
                $table = $this->delivery($table);
                $table->string('state')->default(PalletReturnStateEnum::IN_PROCESS->value);
                $table->dateTimeTz('booked_in_at')->nullable();
                $table->dateTimeTz('settled_at')->nullable();
                foreach (PalletReturnStateEnum::cases() as $state) {
                    $table->dateTimeTz("{$state->snake()}_at")->nullable();
                }
                $table->dateTimeTz('dispatched_at')->nullable();
                $table->dateTimeTz('date')->nullable();
                $table->jsonb('data')->nullable();
                $table->timestampsTz();
                $this->softDeletes($table);
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('return_pallets');
    }
};
