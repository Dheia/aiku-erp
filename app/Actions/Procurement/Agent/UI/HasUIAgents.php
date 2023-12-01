<?php
/*
 * Author: Jonathan Lopez Sanchez <jonathan@ancientwisdom.biz>
 * Created: Tue, 14 Mar 2023 09:31:03 Central European Standard Time, Malaga, Spain
 * Copyright (c) 2023, Inikoo LTD
 */

namespace App\Actions\Procurement\Agent\UI;

use App\Actions\UI\Procurement\ProcurementDashboard;

trait HasUIAgents
{
    public function getBreadcrumbs(): array
    {
        return array_merge(
            (new ProcurementDashboard())->getBreadcrumbs(),
            [
                'grp.procurement.agents.index' => [
                    'route'      => 'grp.procurement.agents.index',
                    'modelLabel' => [
                        'label' => __('agents')
                    ],
                ],
            ]
        );
    }
}
