<?php

namespace App\Actions\Productive;

use Illuminate\Support\Facades\Http;

class InitializeClient extends AbstractAction
{
    /**
     * Initialize the API client for Productive.io
     *
     * @param array $parameters
     * @return \Illuminate\Http\Client\PendingRequest
     */
    public function handle(array $parameters = []): \Illuminate\Http\Client\PendingRequest
    {
        $apiUrl = config('services.productive.api_url');
        $apiToken = config('services.productive.api_token');
        $organizationId = config('services.productive.organization_id');

        if (!$apiToken) {
            throw new \RuntimeException('Productive.io API token not configured');
        }

        if (!$organizationId) {
            throw new \RuntimeException('Productive.io Organization ID not configured');
        }

        if (!$apiUrl) {
            throw new \RuntimeException('Productive.io API URL not configured');
        }

        return Http::baseUrl($apiUrl)
            ->withoutVerifying()
            ->timeout(60) // Increase timeout to 60 seconds
            ->retry(3, 5000) // Retry 3 times with 5 second delay between attempts
            ->withHeaders([
                'X-Auth-Token' => $apiToken,
                'X-Organization-Id' => $organizationId,
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);
    }
}
