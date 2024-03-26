<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Sat, 11 Nov 2023 23:23:00 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('universal_searches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ulid')->nullable();
            $table->boolean('in_organisation')->default(true);
            $table->unsignedSmallInteger('organisation_id')->nullable()->index();
            $table->foreign('organisation_id')->references('id')->on('organisations');
            $table->unsignedSmallInteger('shop_id')->nullable()->index();
            $table->foreign('shop_id')->references('id')->on('shops');
            $table->unsignedSmallInteger('website_id')->nullable()->index();
            $table->foreign('website_id')->references('id')->on('websites');
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->nullableMorphs('model');
            $table->string('section')->nullable();
            $table->string('title');
            $table->string('description')->nullable();
            $table->timestampsTz();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('universal_searches');
    }
};
