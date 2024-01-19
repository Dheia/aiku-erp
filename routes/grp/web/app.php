<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 06 Mar 2023 13:30:46 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */


use App\Actions\SysAdmin\Organisation\UI\IndexOrganisation;
use Illuminate\Support\Facades\Route;

Route::middleware([
    "app",
])->group(function () {


    Route::middleware(["auth"])->group(function () {
        Route::get('/', function () {
            return redirect('/dashboard');

        });

        Route::get('/org', IndexOrganisation::class)->name('index');

        Route::prefix("dashboard")
            ->name("dashboard.")
            ->group(__DIR__."/dashboard.php");
        Route::prefix("supply-chain")
            ->name("supply-chain.")
            ->group(__DIR__."/supply-chain.php");
        Route::prefix("profile")
            ->name("profile.")
            ->group(__DIR__."/profile.php");
        Route::prefix("sysadmin")
            ->name("sysadmin.")
            ->group(__DIR__."/sysadmin.php");
        Route::prefix("org/{organisation}")
            ->name("org.")
            ->group(__DIR__."/org/org.php");
        Route::prefix("models")
            ->name("models.")
            ->group(__DIR__."/models.php");

        /*

        Route::prefix("account")
            ->name("account.")
            ->group(__DIR__."/account.php");
        Route::prefix("bi")
            ->name("business_intelligence.")
            ->group(__DIR__."/business_intelligence.php");
        Route::prefix("crm")
            ->name("crm.")
            ->group(__DIR__."/crm.php");
        Route::prefix("hr")
            ->name("hr.")
            ->group(__DIR__."/hr.php");
        Route::prefix("inventory")
            ->name("inventory.")
            ->group(__DIR__."/warehouses.php");
        Route::prefix("fulfilment")
            ->name("fulfilment.")
            ->group(__DIR__."/fulfilment.php");
        Route::prefix("dropshipping")
            ->name("dropshipping.")
            ->group(__DIR__."/dropshipping.php");
        Route::prefix("production")
            ->name("production.")
            ->group(__DIR__."/production.php");

        Route::prefix("shops")
            ->name("shops.")
            ->group(__DIR__."/shops.php");
        Route::prefix("web")
            ->name("web.")
            ->group(__DIR__."/web.php");
        Route::prefix("products")
            ->name("products.")
            ->group(__DIR__."/products.php");
        Route::prefix("search")
            ->name("search.")
            ->group(__DIR__."/search.php");
        Route::prefix("oms")
            ->name("oms.")
            ->group(__DIR__."/oms.php");
        Route::prefix("dispatch")
            ->name("dispatch.")
            ->group(__DIR__."/dispatch.php");

        Route::prefix("accounting")
            ->name("accounting.")
            ->group(__DIR__."/accounting.php");
        Route::prefix("marketing")
            ->name("marketing.")
            ->group(__DIR__."/marketing.php");
        Route::prefix("sessions")
            ->name("sessions.")
            ->group(__DIR__."/sessions.php");

        Route::prefix("media")
            ->name("media.")
            ->group(__DIR__."/media.php");
        Route::prefix("json")
            ->name("json.")
            ->group(__DIR__."/json.php");
        Route::prefix("firebase")
            ->name("firebase.")
            ->group(__DIR__."/firebase.php");
        Route::prefix("google")
            ->name("google.")
            ->group(__DIR__."/google.php");

*/
    });
    require __DIR__."/auth.php";
});
