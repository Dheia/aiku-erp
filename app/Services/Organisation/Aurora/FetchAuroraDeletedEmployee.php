<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Thu, 16 Feb 2023 11:26:46 Malaysia Time, Ubud, Bali
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Services\Organisation\Aurora;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FetchAuroraDeletedEmployee extends FetchAurora
{
    protected function parseModel(): void
    {
        $auDeletedModel = json_decode(gzuncompress($this->auroraModelData->{'Staff Deleted Metadata'}));


        $this->parsedData['employee'] =
            [
                'contact_name'             => $auDeletedModel->data->{'Staff Name'},
                'email'                    => $auDeletedModel->data->{'Staff Email'},
                'phone'                    => $auDeletedModel->data->{'Staff Telephone'},
                'identity_document_number' => $auDeletedModel->data->{'Staff Official ID'},
                'date_of_birth'            => $this->parseDate($auDeletedModel->data->{'Staff Birthday'}),
                'worker_number'            => $auDeletedModel->data->{'Staff ID'},
                'slug'                     => strtolower($auDeletedModel->data->{'Staff Alias'}),
                'employment_start_at'      => $this->parseDate($auDeletedModel->data->{'Staff Valid From'}),
                'employment_end_at'        => $this->parseDate($auDeletedModel->data->{'Staff Valid To'}),
                'type'                     => Str::snake($auDeletedModel->data->{'Staff Type'}, '-'),


                'source_id'  => $auDeletedModel->data->{'Staff Key'},
                'state'      => match ($auDeletedModel->data->{'Staff Currently Working'}) {
                    'No'    => 'left',
                    default => 'working'
                },
                'data'       => [
                    'address' => $auDeletedModel->data->{'Staff Address'},
                ],
                'deleted_at' => $this->auroraModelData->{'Staff Deleted Date'}
            ];
    }


    protected function fetchData($id): object|null
    {
        return DB::connection('aurora')
            ->table('Staff Deleted Dimension')
            ->where('Staff Deleted Key', $id)->first();
    }
}
