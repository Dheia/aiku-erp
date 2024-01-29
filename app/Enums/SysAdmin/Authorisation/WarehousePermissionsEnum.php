<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Tue, 02 Jan 2024 20:08:38 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2024, Raul A Perusquia Flores
 */

namespace App\Enums\SysAdmin\Authorisation;

use App\Models\Inventory\Warehouse;

enum WarehousePermissionsEnum: string
{
    case WAREHOUSE = 'warehouses';

    case WAREHOUSE_EDIT = 'warehouses.edit';

    case WAREHOUSE_VIEW = 'warehouses.view';

    case INVENTORY      = 'inventory';
    case INVENTORY_EDIT = 'inventory.edit';
    case INVENTORY_VIEW = 'inventory.view';


    case DISPATCHING      = 'dispatching';
    case DISPATCHING_EDIT = 'dispatching.edit';
    case DISPATCHING_VIEW = 'dispatching.view';


    case FULFILMENT = 'fulfilment';

    case FULFILMENT_VIEW = 'fulfilment.view';

    case FULFILMENT_EDIT = 'fulfilment.edit';



    case SUPERVISOR_WAREHOUSES        = 'supervisor-warehouses';
    case SUPERVISOR_INVENTORY         = 'supervisor-inventory';
    case SUPERVISOR_DISPATCHING       = 'supervisor-dispatching';

    case SUPERVISOR_FULFILMENT       = 'supervisor-fulfilment';

    public static function getAllValues(Warehouse $warehouse): array
    {

        $rawPermissionsNames = array_column(WarehousePermissionsEnum::cases(), 'value');

        $permissionsNames    = [];
        foreach ($rawPermissionsNames as $rawPermissionsName) {
            $permissionsNames[] = self::getPermissionName($rawPermissionsName, $warehouse);
        }

        return $permissionsNames;
    }

    public static function getPermissionName(string $rawName, Warehouse $warehouse): string
    {
        $permissionComponents = explode('.', $rawName);
        $permissionComponents = array_merge(array_slice($permissionComponents, 0, 1), [$warehouse->id], array_slice($permissionComponents, 1));

        return join('.', $permissionComponents);
    }

}
