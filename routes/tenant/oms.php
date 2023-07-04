<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Wed, 22 Feb 2023 22:57:23 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */


use App\Actions\Accounting\Invoice\IndexInvoices;
use App\Actions\Accounting\Invoice\ShowInvoice;
use App\Actions\Accounting\Payment\UI\EditPayment;
use App\Actions\Accounting\Payment\UI\ShowPayment;
use App\Actions\Dispatch\DeliveryNote\IndexDeliveryNotes;
use App\Actions\Dispatch\DeliveryNote\ShowDeliveryNote;
use App\Actions\OMS\Order\UI\CreateOrder;
use App\Actions\OMS\Order\UI\IndexOrders;
use App\Actions\OMS\Order\UI\ShowOrder;
use App\Actions\OMS\UI\OMSDashboard;

Route::get('/', OMSDashboard::class)->name('dashboard');
Route::get('/shops/{shop}', OMSDashboard::class)->name('shops.show.dashboard');

Route::get('/create', CreateOrder::class)->name('orders/create');

Route::get('/orders/', [IndexOrders::class, 'inTenant'])->name('orders.index');
Route::get('/orders/{order}', [ShowOrder::class, 'inTenant'])->name('orders.show');
Route::get('/orders/{order}/delivery-notes/{deliveryNote}', [ShowDeliveryNote::class, 'inOrder'])->name('orders.show.delivery-notes.show');
Route::get('/orders/{order}/payments/{payment}', [ShowPayment::class,'inOrder'])->name('orders.show.orders.show.payments.show');
Route::get('/orders/{order}/payments/{payment}/edit', [EditPayment::class, 'inOrder'])->name('orders.show.orders.show.payments.edit');

Route::get('/delivery-notes/', [IndexDeliveryNotes::class, 'inTenant'])->name('delivery-notes.index');
Route::get('/delivery-notes/{deliveryNote}', [ShowDeliveryNote::class, 'inTenant'])->name('delivery-notes.show');

Route::get('/invoices/', [IndexInvoices::class, 'inTenant'])->name('invoices.index');
Route::get('/invoices/{invoice}', [ShowInvoice::class, 'inTenant'])->name('invoices.show');


Route::get('/shops/{shop}/orders/', [IndexOrders::class, 'InShop'])->name('shops.show.orders.index');
Route::get('/shops/{shop}/orders/{order}', [ShowOrder::class, 'InShop'])->name('shops.show.orders.show');
Route::get('/shops/{shop}/orders/{order}/delivery-notes/{deliveryNote}', [ShowDeliveryNote::class, 'InOrderInShop'])->name('shops.show.orders.show.delivery-notes.show');
Route::get('/shops/{shop}/orders/{order}/payments/{payment}', [ShowPayment::class,'InOrderInShop'])->name('shops.show.orders.show.orders.show.payments.show');
Route::get('/shops/{shop}/orders/{order}/payments/{payment}/edit', [EditPayment::class, 'InOrderInShop'])->name('shops.show.orders.show.orders.show.payments.edit');

Route::get('/shops/{shop}/delivery-notes/', [IndexDeliveryNotes::class, 'InShop'])->name('shops.show.delivery-notes.index');
Route::get('/shops/{shop}/delivery-notes/{deliveryNote}', [ShowDeliveryNote::class, 'InShop'])->name('shops.show.delivery-notes.show');

Route::get('/shops/{shop}/invoices/', [IndexInvoices::class, 'InShop'])->name('shops.show.invoices.index');
Route::get('/shops/{shop}/invoices/{invoice}', [ShowInvoice::class, 'InShop'])->name('shops.show.invoices.show');
