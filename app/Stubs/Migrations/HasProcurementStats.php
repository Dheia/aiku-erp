<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Fri, 24 Mar 2023 04:45:48 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Stubs\Migrations;

use App\Enums\Procurement\PurchaseOrderItem\PurchaseOrderItemStateEnum;
use App\Enums\Procurement\PurchaseOrderItem\PurchaseOrderItemStatusEnum;
use App\Enums\Procurement\Supplier\SupplierTypeEnum;
use App\Enums\Procurement\SupplierDelivery\SupplierDeliveryStateEnum;
use App\Enums\Procurement\SupplierDelivery\SupplierDeliveryStatusEnum;
use App\Enums\Procurement\SupplierProduct\SupplierProductQuantityStatusEnum;
use App\Enums\Procurement\SupplierProduct\SupplierProductStateEnum;
use Illuminate\Database\Schema\Blueprint;

trait HasProcurementStats
{
    public function agentStats(Blueprint $table): Blueprint
    {
        $table->unsignedInteger('number_agents')->default(0)->comment('Active agents, status=true');
        $table->unsignedInteger('number_archived_agents')->default(0)->comment('Archived agents, status=false');

        return $table;
    }

    public function suppliersStats(Blueprint $table): Blueprint
    {
        $table->unsignedInteger('number_suppliers')->default(0)->comment('Active suppliers, status=true');
        $table->unsignedInteger('number_archived_suppliers')->default(0)->comment('Archived suppliers status=false');



        foreach (SupplierTypeEnum::cases() as $supplierType) {
            $table->unsignedBigInteger('number_suppliers_type_'.$supplierType->snake())->default(0)
                ->comment('Active suppliers. status=true,type='.$supplierType->value);
            $table->unsignedBigInteger('number_archived_suppliers_type_'.$supplierType->snake())->default(0)
                ->comment('Archived suppliers. status=false,type='.$supplierType->value);

        }

        return $table;
    }

    public function supplierProductsStats(Blueprint $table): Blueprint
    {
        $table->unsignedInteger('number_supplier_products')->default(0)->comment('Number supplier products (all excluding discontinued)');
        $table->unsignedInteger('number_supplier_deliveries')->default(0)->comment('Number supplier deliveries (all excluding discontinued)');
        $table->unsignedInteger('supplier_products_count')->default(0)->comment('Number supplier products');


        foreach (SupplierProductStateEnum::cases() as $productState) {
            $table->unsignedBigInteger('number_supplier_products_state_'.$productState->snake())->default(0);
        }


        foreach (SupplierProductQuantityStatusEnum::cases() as $productStockQuantityStatus) {
            $table->unsignedBigInteger('number_supplier_products_stock_quantity_status_'.$productStockQuantityStatus->snake())->default(0);
        }


        return $table;
    }

    public function purchaseOrdersStats(Blueprint $table): Blueprint
    {
        $table->unsignedInteger('number_purchase_orders')->default(0)->comment('Number purchase orders (except cancelled and failed) ');
        $table->unsignedInteger('number_open_purchase_orders')->default(0)->comment('Number purchase orders (except creating, settled)');


        foreach (PurchaseOrderItemStateEnum::cases() as $purchaseOrderState) {
            $table->unsignedInteger('number_purchase_orders_state_'.$purchaseOrderState->snake())->default(0);
        }


        foreach (PurchaseOrderItemStatusEnum::cases() as $purchaseOrderStatus) {
            $table->unsignedInteger('number_purchase_orders_status_'.$purchaseOrderStatus->snake())->default(0);
        }


        return $table;
    }

    public function supplierDeliveriesStats(Blueprint $table): Blueprint
    {
        $table->unsignedInteger('number_deliveries')->default(0)->comment('Number supplier deliveries (except cancelled)');

        foreach (SupplierDeliveryStateEnum::cases() as $supplierDeliveryState) {
            $table->unsignedBigInteger('number_supplier_deliveries_state_'.$supplierDeliveryState->snake())->default(0);
        }

        foreach (SupplierDeliveryStatusEnum::cases() as $supplierDeliveryStatus) {
            $table->unsignedBigInteger('number_supplier_deliveries_status_'.$supplierDeliveryStatus->snake())->default(0);
        }

        return $table;
    }


}
