<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 30 Jan 2024 12:37:34 Malaysia Time, Bali Office, Indonesia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

use App\Actions\Fulfilment\Pallet\UI\IndexPallets;
use App\Actions\Fulfilment\Pallet\UI\ShowPallet;
use App\Actions\Fulfilment\PalletDelivery\UI\IndexPalletDeliveries;
use App\Actions\Fulfilment\PalletDelivery\UI\ShowPalletDelivery;
use App\Actions\Fulfilment\PalletReturn\UI\IndexPalletReturns;
use App\Actions\Fulfilment\PalletReturn\UI\ShowPalletReturn;
use App\Actions\UI\Fulfilment\ShowFulfilmentDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', ShowFulfilmentDashboard::class)->name('dashboard');
Route::get('/pallets', [IndexPallets::class, 'inWarehouse'])->name('pallets.index');
Route::get('/pallets/{pallet}', [ShowPallet::class, 'inWarehouse'])->name('pallets.show');

Route::get('deliveries', [IndexPalletDeliveries::class, 'inWarehouse'])->name('pallet-deliveries.index');
Route::get('deliveries/{palletDelivery}', [ShowPalletDelivery::class, 'inWarehouse'])->name('pallet-deliveries.show');

Route::get('returns', [IndexPalletReturns::class, 'inWarehouse'])->name('pallet-returns.index');
Route::get('returns/{palletReturn}', [ShowPalletReturn::class, 'inWarehouse'])->name('pallet-returns.show');
