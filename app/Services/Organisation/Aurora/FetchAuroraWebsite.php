<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Wed, 12 Oct 2022 18:07:21 Central European Summer Time, Benalmádena, Malaga Spain
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Services\Organisation\Aurora;

use App\Actions\Utils\Abbreviate;
use App\Enums\Web\Website\WebsiteEngineEnum;
use App\Enums\Web\Website\WebsiteStateEnum;
use App\Models\Web\Website;
use Illuminate\Support\Facades\DB;

class FetchAuroraWebsite extends FetchAurora
{
    public function fetch(int $id): ?array
    {
        $this->auroraModelData = $this->fetchData($id);


        $code = strtolower($this->auroraModelData->{'Website Code'});
        $code = preg_replace('/\.com$/', '', $code);
        $code = preg_replace('/\.eu$/', '', $code);
        $code = preg_replace('/\.biz$/', '', $code);


        $sourceId = $this->organisation->id.':'.$this->auroraModelData->{'Website Key'};
        if (Website::where('code', $code)->whereNot('source_id', $sourceId)->exists()) {
            $code = $code.strtolower(Abbreviate::run(string: $this->organisation->slug, maximumLength: 2));
        }
        $this->auroraModelData->code = $code;
        $this->parseModel();

        return $this->parsedData;
    }

    protected function parseModel(): void
    {
        $this->parsedData['shop'] = $this->parseShop($this->organisation->id.':'.$this->auroraModelData->{'Website Store Key'});

        $state = match ($this->auroraModelData->{'Website Status'}) {
            'Active' => WebsiteStateEnum::LIVE,
            'Closed' => WebsiteStateEnum::CLOSED,
            default  => WebsiteStateEnum::IN_PROCESS,
        };


        $domain = preg_replace('/^www\./', '', strtolower($this->auroraModelData->{'Website URL'}));


        $this->parsedData['website'] =
            [
                'engine'      => WebsiteEngineEnum::AURORA,
                'name'        => $this->auroraModelData->{'Website Name'},
                'code'        => $this->auroraModelData->code,
                'domain'      => $domain,
                'state'       => $state,
                'status'      => $this->auroraModelData->{'Website Status'} === 'Active',
                'source_id'   => $this->organisation->id.':'.$this->auroraModelData->{'Website Key'},
            ];

        if($launchedAt=$this->parseDate($this->auroraModelData->{'Website Launched'})) {
            $this->parsedData['website']['launched_at']=$launchedAt;
        }

        if($createdAt=$this->parseDate($this->auroraModelData->{'Website From'})) {
            $this->parsedData['website']['created_at']=$createdAt;
        }


    }


    protected function fetchData($id): object|null
    {
        return DB::connection('aurora')
            ->table('Website Dimension')
            ->where('Website Key', $id)->first();
    }
}
