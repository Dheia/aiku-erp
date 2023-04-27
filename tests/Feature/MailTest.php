<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 25 Apr 2023 16:05:15 Malaysia Time, Sanur, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace Tests\Feature;

use App\Actions\Mail\DispatchedEmail\StoreDispatchEmail;
use App\Actions\Mail\Mailshot\StoreMailshot;
use App\Actions\Marketing\Shop\StoreShop;
use App\Models\Mail\Mailshot;
use App\Models\Marketing\Shop;
use App\Models\Tenancy\Tenant;

beforeAll(fn () => loadDB('d3_with_tenants.dump'));

beforeEach(function () {
    $tenant = Tenant::where('slug', 'agb')->first();
    $tenant->makeCurrent();
});

test('get outbox from shop', function () {
    $shop   = StoreShop::make()->action(Shop::factory()->definition());
    $outbox = $shop->outboxes()->first();
    $this->assertModelExists($outbox);

    return $outbox;
});


test('create mailshot', function ($outbox) {
    $mailshot = StoreMailshot::make()->action($outbox, Mailshot::factory()->definition());
    $this->assertModelExists($mailshot);

    return $mailshot;
})->depends('get outbox from shop');

test('update mailshot', function ($mailshot) {
})->depends('create mailshot')->todo();

test('create dispatched email in outbox', function ($outbox) {
    $dispatchedEmail = StoreDispatchEmail::make()->action(
        $outbox,
        fake()->email,
        []
    );
    $this->assertModelExists($dispatchedEmail);
})->depends('get outbox from shop');

test('create dispatched email in mailshot', function ($mailshot) {
    $dispatchedEmail = StoreDispatchEmail::make()->action(
        $mailshot,
        fake()->email,
        []
    );
    $this->assertModelExists($dispatchedEmail);

    return $dispatchedEmail;
})->depends('create mailshot');


test('update dispatched email', function ($dispatchedEmail) {
})->depends('create dispatched email in outbox')->todo();
