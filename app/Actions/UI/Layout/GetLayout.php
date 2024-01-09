<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Fri, 08 Dec 2023 22:08:13 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\UI\Layout;

use App\Http\Resources\SysAdmin\Group\GroupResource;
use App\Http\Resources\SysAdmin\Organisation\UserOrganisationResource;
use App\Models\SysAdmin\User;
use Lorisleiva\Actions\Concerns\AsAction;

class GetLayout
{
    use AsAction;

    public function handle(?User $user): array
    {
        if (!$user) {
            return [];
        }
        return [

            'group'         => GroupResource::make(app('group'))->getArray(),
            'organisations' => UserOrganisationResource::collectionForUser($user->authorisedOrganisations, $user),
            'navigation'    => [
                'grp' => GetGroupNavigation::run($user),
                'org' => GetOrganisationsLayout::run($user),
            ]


        ];
    }
}
