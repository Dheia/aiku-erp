<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Fri, 24 Mar 2023 02:16:56 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Enums\SupplyChain\Stock;

use App\Enums\EnumHelperTrait;
use App\Models\SysAdmin\Group;

enum StockStateEnum: string
{
    use EnumHelperTrait;

    case IN_PROCESS        = 'in-process';
    case ACTIVE            = 'active';
    case DISCONTINUED      = 'discontinued';

    public static function labels(): array
    {
        return [
            'in-process'    => __('In process'),
            'active'        => __('Active'),
            'discontinued'  => __('Discontinued'),
        ];
    }

    public static function stateIcon(): array
    {
        return [
            'in-process' => [
                'tooltip' => __('in process'),
                'icon'    => 'fal fa-seedling',
                'class'   => 'text-indigo-500'
            ],
            'active'    => [
                'tooltip' => __('contacted'),
                'icon'    => 'fal fa-chair',
                'class'   => 'text-green-500'
            ],
            'discontinued'      => [
                'tooltip' => __('discontinued'),
                'icon'    => 'fal fa-laugh',
                'class'   => 'text-red-500'
            ],
        ];
    }

    public static function count(Group $parent): array
    {
        $stats = $parent->inventoryStats;

        return [
            'in-process'        => $stats->number_stocks_state_in_process,
            'active'            => $stats->number_stocks_state_active,
            'discontinued'      => $stats->number_stocks_state_discontinued,
        ];
    }

}
