<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 04 Dec 2023 16:24:47 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SysAdmin\User;

use App\Actions\GrpAction;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateUsers;
use App\Actions\SysAdmin\User\Hydrators\UserHydrateUniversalSearch;
use App\Actions\SysAdmin\User\UI\SetUserAvatar;
use App\Enums\SysAdmin\User\UserAuthTypeEnum;
use App\Models\HumanResources\Employee;
use App\Models\SupplyChain\Agent;
use App\Models\SupplyChain\Supplier;
use App\Models\SysAdmin\Guest;
use App\Models\SysAdmin\User;
use App\Rules\AlphaDashDot;
use App\Rules\IUnique;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;

class StoreUser extends GrpAction
{
    public function handle(Guest|Employee|Supplier|Agent $parent, array $modelData = []): User
    {
        data_set($modelData, 'group_id', $parent->group_id);
        data_set($modelData, 'contact_name', $parent->contact_name);

        $type = match (class_basename($parent)) {
            'Guest', 'Employee', 'Supplier', 'Agent' => strtolower(class_basename($parent)),
            default => null
        };

        data_set($modelData, 'type', $type);

        /** @var User $user */
        $user = $parent->user()->create($modelData);

        $user->stats()->create();
        $user->refresh();
        SetUserAvatar::dispatch(userable: $user, saveHistory: false);
        UserHydrateUniversalSearch::dispatch($user);
        GroupHydrateUsers::dispatch($user->group);


        return $user;
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("sysadmin.edit");
    }

    public function rules(): array
    {
        return [
            'username'        => ['required', new AlphaDashDot(), 'iunique:users,username', Rule::notIn(['export', 'create'])],
            'password'        => ['required', app()->isLocal() || app()->environment('testing') ? null : Password::min(8)->uncompromised()],
            'legacy_password' => ['sometimes', 'string'],
            'email'           => ['sometimes', 'nullable', 'email',
                                  new IUnique(
                                      table: 'employees',
                                      extraConditions: [
                                          [
                                              'column' => 'group_id',
                                              'value'  => $this->group->id
                                          ],
                                      ]
                                  ),

                ],
            'contact_name'    => ['sometimes', 'string', 'max:255'],
            'reset_password'  => ['sometimes', 'boolean'],
            'auth_type'       => ['sometimes', Rule::enum(UserAuthTypeEnum::class)],
            'status'          => ['sometimes', 'boolean'],
            'source_id'       => ['sometimes', 'string'],
            'created_at'      => ['sometimes', 'date'],
            'language_id'     => ['sometimes', 'required', 'exists:languages,id'],
        ];
    }

    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        if ($this->get('username') != strtolower($this->get('username'))) {
            $validator->errors()->add('user', __('Username must be lowercase.'));
        }
    }


    public function action(Guest|Employee $parent, array $modelData = []): User
    {
        $this->asAction = true;

        $this->initialisation($parent->group, $modelData);

        return $this->handle($parent, $this->validatedData);
    }


}
