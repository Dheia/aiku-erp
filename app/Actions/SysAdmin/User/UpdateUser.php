<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Mon, 04 Dec 2023 16:24:47 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SysAdmin\User;

use App\Actions\GrpAction;
use App\Actions\SysAdmin\Group\Hydrators\GroupHydrateUsers;
use App\Actions\Traits\WithActionUpdate;
use App\Enums\SysAdmin\User\UserAuthTypeEnum;
use App\Http\Resources\SysAdmin\UserResource;
use App\Models\SysAdmin\User;
use App\Rules\AlphaDashDot;
use App\Rules\IUnique;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;

class UpdateUser extends GrpAction
{
    use WithActionUpdate;

    private bool $asAction = false;

    private User $user;

    public function handle(User $user, array $modelData): User
    {


        $user= $this->update($user, $modelData, ['profile', 'settings']);

        if($user->wasChanged('status')) {
            GroupHydrateUsers::run($user->group);
        }

        return $user;
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }
        return  $request->user()->hasPermissionTo('sysadmin.edit');

    }

    public function rules(): array
    {
        return [
            'username'        => ['sometimes','required', new AlphaDashDot(),

                                   Rule::notIn(['export', 'create']),
                                  new IUnique(
                                      table: 'employees',
                                      extraConditions: [

                                          [
                                              'column'   => 'id',
                                              'operator' => '!=',
                                              'value'    => $this->user->id
                                          ],
                                      ]
                                  ),




            ],
            'password'        => ['sometimes','required', app()->isLocal() || app()->environment('testing') ? null : Password::min(8)->uncompromised()],
            'legacy_password' => ['sometimes', 'string'],
            'email'           => ['sometimes', 'nullable', 'email',
                                  new IUnique(
                                      table: 'employees',
                                      extraConditions: [
                                          [
                                              'column' => 'group_id',
                                              'value'  => $this->group->id
                                          ],
                                          [
                                              'column'   => 'id',
                                              'operator' => '!=',
                                              'value'    => $this->user->id
                                          ],
                                      ]
                                  ),
                ],
            'contact_name'    => ['sometimes', 'string', 'max:255'],
            'reset_password'  => ['sometimes', 'boolean'],
            'auth_type'       => ['sometimes', Rule::enum(UserAuthTypeEnum::class)],
            'status'          => ['sometimes', 'boolean'],
            'language_id'     => ['sometimes', 'required', 'exists:languages,id'],
        ];
    }


    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        if ($this->has('username') and $this->get('username') != strtolower($this->get('username'))) {
            $validator->errors()->add('user', __('Username must be lowercase.'));
        }
    }


    public function asController(User $user, ActionRequest $request): User
    {
        $this->user=$user;
        $this->initialisation($user->group, $request);
        return $this->handle($user, $this->validatedData);
    }

    public function action(User $user, $modelData): User
    {
        $this->user     =$user;
        $this->asAction = true;
        $this->initialisation($user->group, $modelData);

        return $this->handle($user, $this->validatedData);

    }

    public function jsonResponse(User $user): UserResource
    {
        return new UserResource($user);
    }
}
