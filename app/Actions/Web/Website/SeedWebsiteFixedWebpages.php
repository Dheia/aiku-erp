<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 21 Sep 2023 11:35:41 Malaysia Time, Pantai Lembeng, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Web\Website;

use App\Actions\OrgAction;
use App\Actions\Traits\WithActionUpdate;
use App\Actions\Web\Webpage\StoreWebpage;
use App\Enums\Web\Webpage\WebpagePurposeEnum;
use App\Enums\Web\Webpage\WebpageStateEnum;
use App\Enums\Web\Webpage\WebpageTypeEnum;
use App\Models\SysAdmin\Organisation;
use App\Models\Web\Webpage;
use App\Models\Web\Website;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\ActionRequest;

class SeedWebsiteFixedWebpages extends OrgAction
{
    use WithActionUpdate;


    public function handle(Website $website): Website
    {
        $storefront = StoreWebpage::make()->action($website, [
            'code'     => 'storefront',
            'type'     => WebpageTypeEnum::STOREFRONT,
            'purpose'  => WebpagePurposeEnum::STOREFRONT,
            'is_fixed' => true,
            'state'    => WebpageStateEnum::READY,
        ]);

        $website->update(
            [
                'storefront_id' => $storefront->id
            ]
        );

        foreach (Storage::disk('datasets')->files('webpages/common') as $file) {
            $this->addWebpage($storefront, $file);
        }

        foreach (Storage::disk('datasets')->files('webpages/'.$website->type->value) as $file) {
            $this->addWebpage($storefront, $file);
        }


        return $website;
    }

    private function addWebpage(Webpage $home, $file): void
    {
        $modelData = json_decode(Storage::disk('datasets')->get($file), true);


        StoreWebpage::make()->action(
            $home,
            array_merge(
                $modelData,
                [
                    'is_fixed' => true,
                    'ready_at' => now(),
                    'state'    => WebpageStateEnum::READY,
                ]
            )
        );
    }

    public function authorize(ActionRequest $request): bool
    {
        if ($this->asAction) {
            return true;
        }

        return $request->user()->hasPermissionTo("shops.{$this->shop->id}.edit");
    }


    public function asController(Organisation $organisation, Website $website, ActionRequest $request): Website
    {
        $this->initialisationFromShop($website->shop, $request);

        return $this->handle($website);
    }

    public function getCommandSignature(): string
    {
        return 'website:seed-fixed-webpages {website}';
    }

    public function asCommand(Command $command): int
    {
        try {
            $website = Website::where('slug', $command->argument('website'))->firstOrFail();
        } catch (Exception $e) {
            $command->error($e->getMessage());

            return 1;
        }

        $this->handle($website);

        return 0;
    }

}
