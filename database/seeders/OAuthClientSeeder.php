<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OAuthClientSeeder extends Seeder
{
    public function run(): void
    {
        // Create OAuth client for SeferX Yemek
        $clientId = env('SEFERX_YEMEK_CLIENT_ID', Str::uuid()->toString());
        $clientSecret = env('SEFERX_YEMEK_CLIENT_SECRET', Str::random(40));
        $baseUrl = env('SEFERX_YEMEK_URL', 'http://seferxyemek.test');

        // Multiple redirect URIs for different environments
        $redirectUris = [
            'https://seferxyemek.com/owner/logistics/callback/seferx-lojistik',
            'http://seferxyemek.com/owner/logistics/callback/seferx-lojistik',
            rtrim($baseUrl, '/') . '/owner/logistics/callback/seferx-lojistik',
        ];
        $redirectUris = array_unique($redirectUris);

        // Check if client already exists by name
        $existingClient = DB::table('oauth_clients')
            ->where('name', 'SeferX Yemek')
            ->first();

        if (!$existingClient) {
            DB::table('oauth_clients')->insert([
                'id' => $clientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => 'SeferX Yemek',
                'secret' => $clientSecret,
                'provider' => null,
                'redirect_uris' => json_encode(array_values($redirectUris)),
                'grant_types' => json_encode(['authorization_code', 'refresh_token']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("OAuth Client created:");
            $this->command->info("Client ID: {$clientId}");
            $this->command->info("Client Secret: {$clientSecret}");
            $this->command->info("Redirect URIs: " . implode(', ', $redirectUris));
            $this->command->newLine();
            $this->command->warn("Add these to seferxyemek .env file:");
            $this->command->line("SEFERX_LOGISTICS_CLIENT_ID={$clientId}");
            $this->command->line("SEFERX_LOGISTICS_CLIENT_SECRET={$clientSecret}");
        } else {
            // Update redirect URIs for existing client
            DB::table('oauth_clients')
                ->where('id', $existingClient->id)
                ->update(['redirect_uris' => json_encode(array_values($redirectUris))]);

            $this->command->info("OAuth Client updated: {$existingClient->name}");
            $this->command->info("Client ID: {$existingClient->id}");
            $this->command->info("Redirect URIs updated.");
        }
    }
}
