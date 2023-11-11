<?php
/*
 * Author: Artha <artha@aw-advantage.com>
 * Created: Tue, 11 Jul 2023 10:03:34 Central Indonesia Time, Sanur, Bali, Indonesia
 * Copyright (c) 2023, Raul A Perusquia Flores
 */

namespace App\Actions\Google\Drive;

use App\Actions\Google\Drive\Traits\WithTokenPath;
use Exception;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

class CallbackClientGoogleDrive
{
    use AsAction;
    use WithTokenPath;

    /**
     * @throws \Exception
     */
    public function handle(): RedirectResponse
    {
        $client = new Google_Client();

        $tokenPath       = $this->getTokenPath();
        $authCode        = request()->query('code');
        $organisation    = app('currentTenant');

        $client->setRedirectUri('http://localhost:5173');
        $client->setApplicationName('Aiku google drive manager');
        $client->setAuthConfig([
            'client_id'     => Arr::get($organisation->settings, 'google.id'),
            'client_secret' => Arr::get($organisation->settings, 'google.secret')
        ]);

        $client->setAccessType('offline');
        $client->setScopes(
            [
                Google_Service_Drive::DRIVE_METADATA,
                Google_Service_Drive::DRIVE_FILE,
                Google_Service_Drive::DRIVE
            ]
        );

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        $client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }

        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));

        return redirect()->route('sysadmin.settings.edit');
    }
}
