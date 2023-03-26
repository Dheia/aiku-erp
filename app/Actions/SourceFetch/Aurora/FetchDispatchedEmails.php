<?php
/*
 * Author: Raul Perusquia <raul@inikoo.com>
 * Created: Fri, 10 Mar 2023 20:59:01 Malaysia Time, Kuala Lumpur, Malaysia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\SourceFetch\Aurora;

use App\Actions\Mail\DispatchedEmail\StoreDispatchEmail;
use App\Actions\Mail\DispatchedEmail\UpdateDispatchedEmail;
use App\Models\Mail\DispatchedEmail;
use App\Services\Tenant\SourceTenantService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\NoReturn;

class FetchDispatchedEmails extends FetchAction
{
    public string $commandSignature = 'fetch:dispatched-emails {tenants?*} {--s|source_id=}';


    #[NoReturn] public function handle(SourceTenantService $tenantSource, int $tenantSourceId): ?DispatchedEmail
    {
        if ($shopData = $tenantSource->fetchDispatchedEmail($tenantSourceId)) {
            if ($dispatchedEmail = DispatchedEmail::where('source_id', $shopData['dispatchedEmail']['source_id'])
                ->first()) {
                $dispatchedEmail = UpdateDispatchedEmail::run(
                    dispatchedEmail: $dispatchedEmail,
                    modelData: $shopData['dispatchedEmail']
                );
            } else {
                $dispatchedEmail = StoreDispatchEmail::run(
                    parent: $shopData['parent'],
                    email: $shopData['email'],
                    modelData: $shopData['dispatchedEmail']
                );
            }

            DB::connection('aurora')->table('Email Tracking Dimension')
                ->where('Email Tracking Key', $dispatchedEmail->source_id)
                ->update(['aiku_id' => $dispatchedEmail->id]);
            return $dispatchedEmail;
        }

        return null;
    }


    public function getModelsQuery(): Builder
    {
        return DB::connection('aurora')
            ->table('Email Tracking Dimension')
            ->select('Email Tracking Key as source_id')
            ->orderBy('source_id');
    }

    public function count(): ?int
    {
        return DB::connection('aurora')->table('Email Tracking Dimension')->count();
    }
}
