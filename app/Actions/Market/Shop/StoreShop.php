<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Fri, 26 Aug 2022 01:35:48 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia F
 */

namespace App\Actions\Market\Shop;

use App\Actions\Accounting\PaymentAccount\StorePaymentAccount;
use App\Actions\Assets\Currency\SetCurrencyHistoricFields;
use App\Actions\Fulfilment\Fulfilment\StoreFulfilment;
use App\Actions\Helpers\Query\Seeders\ProspectQuerySeeder;
use App\Actions\Mail\Outbox\SeedShopOutboxes;
use App\Actions\OrgAction;
use App\Actions\Mail\Outbox\StoreOutbox;
use App\Actions\SysAdmin\Organisation\Hydrators\OrganisationHydrateMarket;
use App\Actions\SysAdmin\User\UserAddRoles;
use App\Actions\Traits\Rules\WithShopRules;
use App\Enums\Accounting\PaymentAccount\PaymentAccountTypeEnum;
use App\Enums\Helpers\SerialReference\SerialReferenceModelEnum;
use App\Enums\Mail\Outbox\OutboxTypeEnum;
use App\Enums\Market\Shop\ShopTypeEnum;
use App\Enums\SysAdmin\Authorisation\RolesEnum;
use App\Models\Assets\Country;
use App\Models\Assets\Currency;
use App\Models\Assets\Language;
use App\Models\Assets\Timezone;
use App\Models\SysAdmin\Organisation;
use App\Models\Market\Shop;
use App\Models\SysAdmin\Role;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;

class StoreShop extends OrgAction
{
    use WithShopRules;

    public function handle(Organisation $organisation, array $modelData): Shop
    {
        data_set($modelData, 'group_id', $organisation->group_id);

        /** @var Shop $shop */
        $shop = $organisation->shops()->create($modelData);
        $shop->stats()->create();
        $shop->accountingStats()->create();
        $shop->mailStats()->create();
        $shop->crmStats()->create();
        $shop->salesStats()->create([
            'currency_id' => $shop->currency_id
        ]);


        $shop->serialReferences()->create(
            [
                'model'           => SerialReferenceModelEnum::CUSTOMER,
                'organisation_id' => $organisation->id,
            ]
        );
        $shop->serialReferences()->create(
            [
                'model'           => SerialReferenceModelEnum::ORDER,
                'organisation_id' => $organisation->id,
            ]
        );


        if ($shop->type == ShopTypeEnum::FULFILMENT) {
            //it must use run to bypass rules
            StoreFulfilment::make()->run($shop, []);
        } else {
            SeedShopPermissions::run($shop);

            $orgAdmins = $organisation->group->users()->with('roles')->get()->filter(
                fn ($user) => $user->roles->where('name', "org-admin-$organisation->id")->toArray()
            );

            foreach ($orgAdmins as $orgAdmin) {
                UserAddRoles::run($orgAdmin, [
                    Role::where('name', RolesEnum::getRoleName('shop-admin', $shop))->first()
                ]);
            }
        }


        SetCurrencyHistoricFields::run($shop->currency, $shop->created_at);

        $paymentAccount       = StorePaymentAccount::make()->action(
            $organisation->accountsServiceProvider(),
            [
                'code' => 'accounts-'.$shop->slug,
                'name' => 'Accounts '.$shop->code,
                'type' => PaymentAccountTypeEnum::ACCOUNT->value
            ]
        );
        $paymentAccount->slug = 'accounts-'.$shop->slug;
        $paymentAccount->save();
        $shop = AttachPaymentAccountToShop::run($shop, $paymentAccount);

        foreach (OutboxTypeEnum::cases() as $case) {
            if ($case->scope() == 'shop') {
                $mailroom = $organisation->group->mailrooms()->where('code', $case->mailroomCode()->value)->first();

                StoreOutbox::run(
                    $mailroom,
                    [
                        'shop_id' => $shop->id,
                        'name'    => $case->label(),
                        'type'    => str($case->value)->camel()->kebab()->value(),

                    ]
                );
            }
        }

        OrganisationHydrateMarket::dispatch($organisation);
        ProspectQuerySeeder::run($shop);
        SeedShopOutboxes::run($shop);

        return $shop;
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("shops.{$this->organisation->id}.edit");
    }

    public function rules(): array
    {
        return $this->getStoreShopRules();
    }


    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        if ($request->get('identity_document_number') and !$request->get('identity_document_type')) {
            $validator->errors()->add('contact_name', 'document type required');
        }
        if ($request->get('identity_document_type') and !$request->get('identity_document_number')) {
            $validator->errors()->add('contact_name', 'document number required');
        }
    }

    public function action(Organisation $organisation, array $modelData): Shop
    {
        $this->asAction = true;
        $this->initialisation($organisation, $modelData);
        return $this->handle($organisation, $this->validatedData);
    }

    public function asController(Organisation $organisation, ActionRequest $request): Shop
    {
        $this->initialisation($organisation, $request);

        return $this->handle($organisation, $this->validatedData);
    }


    public string $commandSignature = 'shop:create {organisation : organisation slug} {code} {name} {type}
    {--contact_name=} {--company_name=} {--email=} {--phone=} {--identity_document_number=} {--identity_document_type=} {--country=} {--currency=} {--language=} {--timezone=}';


    public function asCommand(Command $command): int
    {
        $this->asAction = true;

        try {
            $organisation = Organisation::where('slug', $command->argument('organisation'))->firstOrFail();
        } catch (Exception $e) {
            $command->error($e->getMessage());

            return 1;
        }
        $this->organisation = $organisation;
        setPermissionsTeamId($organisation->group->id);

        if ($command->option('country')) {
            try {
                $country = Country::where('code', $command->option('country'))->firstOrFail();
            } catch (Exception $e) {
                $command->error($e->getMessage());

                return 1;
            }
        } else {
            $country = $organisation->country;
        }

        if ($command->option('currency')) {
            try {
                $currency = Currency::where('code', $command->option('currency'))->firstOrFail();
            } catch (Exception $e) {
                $command->error($e->getMessage());

                return 1;
            }
        } else {
            $currency = $organisation->currency;
        }

        if ($command->option('language')) {
            try {
                $language = Language::where('code', $command->option('language'))->firstOrFail();
            } catch (Exception $e) {
                $command->error($e->getMessage());

                return 1;
            }
        } else {
            $language = $organisation->language;
        }

        if ($command->option('timezone')) {
            try {
                $timezone = Timezone::where('name', $command->option('timezone'))->firstOrFail();
            } catch (Exception $e) {
                $command->error($e->getMessage());

                return 1;
            }
        } else {
            $timezone = $organisation->timezone;
        }


        $this->setRawAttributes([
            'code'        => $command->argument('code'),
            'name'        => $command->argument('name'),
            'type'        => $command->argument('type'),
            'timezone_id' => $timezone->id,
            'country_id'  => $country->id,
            'currency_id' => $currency->id,
            'language_id' => $language->id,
        ]);

        try {
            $validatedData = $this->validateAttributes();
        } catch (Exception $e) {
            $command->error($e->getMessage());

            return 1;
        }

        $shop = $this->handle($organisation, $validatedData);

        $command->info("Shop $shop->code created successfully 🎉");

        return 0;
    }

    public function htmlResponse(Shop $shop): RedirectResponse
    {
        return Redirect::route('grp.org.shops.show', [$this->organisation->slug, $shop->slug]);
    }

}
