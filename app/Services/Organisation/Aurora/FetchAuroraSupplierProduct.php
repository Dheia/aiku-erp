<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Tue, 25 Oct 2022 21:38:36 British Summer Time, Sheffield, UK
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Services\Organisation\Aurora;

use App\Enums\Procurement\SupplierProduct\SupplierProductStateEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FetchAuroraSupplierProduct extends FetchAurora
{
    use WithAuroraParsers;

    protected function parseModel(): void
    {

        if($this->auroraModelData->aiku_ignore=='Yes') {
            return;
        }


        $supplierDeletedAt = $this->parseDate($this->auroraModelData->{'Supplier Valid To'});
        if ($this->auroraModelData->{'Supplier Type'} != 'Archived') {
            $supplierDeletedAt = null;
        }

        $supplierSourceSlug = Str::kebab(strtolower($this->auroraModelData->{'Supplier Code'}));
        if ($supplierDeletedAt) {
            $supplierSourceSlug .= '-deleted';
        }


        $supplier = $this->parseSupplier($supplierSourceSlug);


        if(!$supplier) {
            return;
        }


        $tradeUnitReference  = $this->cleanTradeUnitReference($this->auroraModelData->{'Part Reference'});
        $tradeUnitSlug       = Str::lower($tradeUnitReference);

        $this->parsedData['trade_unit']=$this->parseTradeUnit(
            $tradeUnitSlug,
            $this->auroraModelData->{'Part SKU'}
        );


        $this->parsedData['supplier'] =$supplier;

        $data       = [];
        $settings   = [];

        $status = true;
        if ($this->auroraModelData->{'Supplier Part Status'} == 'NoAvailable') {
            $status = false;
        }
        $state = match ($this->auroraModelData->{'Supplier Part Status'}) {
            'Discontinued', 'NoAvailable' =>SupplierProductStateEnum::DISCONTINUED,
            default        => SupplierProductStateEnum::ACTIVE,
        };

        if ($state==SupplierProductStateEnum::DISCONTINUED) {
            $status = false;
        }

        if ($this->auroraModelData->{'Supplier Part From'} == '0000-00-00 00:00:00') {
            $created_at = null;
        } else {
            $created_at = $this->auroraModelData->{'Supplier Part From'};
        }

        $data['raw_price'] = $this->auroraModelData->{'Supplier Part Unit Cost'} ?? 0;


        $stock_quantity_status = match ($this->auroraModelData->{'Part Stock Status'}) {
            'Out_Of_Stock', 'Error' => 'out-of-stock',
            default => strtolower($this->auroraModelData->{'Part Stock Status'})
        };

        $partReference      = $this->cleanTradeUnitReference($this->auroraModelData->{'Part Reference'});
        $sourceSlugInterOrg = $supplier->source_slug.':'.$this->auroraModelData->{'Supplier Part Packages Per Carton'}.':'.$this->auroraModelData->{'Part Units Per Package'}.':'.Str::kebab(strtolower($partReference));


        $sourceSlug = $supplier->source_slug.':'.Str::kebab(strtolower($partReference));



        $name= $this->auroraModelData->{'Supplier Part Description'};
        if($name=='') {
            $name=$this->auroraModelData->{'Supplier Part Reference'};
        }


        $code=$this->auroraModelData->{'Supplier Part Reference'};
        $code=str_replace('&', 'and', $code);

        $this->parsedData['supplierProduct'] =
            [
                'code' => $code,
                'name' => $name,

                'cost'             => round($this->auroraModelData->{'Supplier Part Unit Cost'} ?? 0, 2),
                'units_per_pack'   => $this->auroraModelData->{'Part Units Per Package'},
                'units_per_carton' => $this->auroraModelData->{'Supplier Part Packages Per Carton'} * $this->auroraModelData->{'Part Units Per Package'},


                'status'                => $status,
                'state'                 => $state,
                'stock_quantity_status' => $stock_quantity_status,

                'data'        => $data,
                'settings'    => $settings,
                'created_at'  => $created_at,
                'source_slug' => $sourceSlug,
                'source_id'   => $this->organisation->id.':'.$this->auroraModelData->{'Supplier Part Key'}
            ];
    }


    protected function fetchData($id): object|null
    {
        return DB::connection('aurora')
            ->table('Supplier Part Dimension as ssp')
            ->leftjoin('Supplier Dimension', 'Supplier Part Supplier Key', 'Supplier Key')
            ->leftjoin('Part Dimension', 'Supplier Part Part SKU', 'Part SKU')
            ->where('ssp.aiku_ignore', 'No')
            ->where('Supplier Part Key', $id)->first();
    }
}
