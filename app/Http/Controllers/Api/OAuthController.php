<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\TokenRepository;

class OAuthController extends Controller
{
    public function __construct(
        protected ClientRepository $clients,
        protected TokenRepository $tokens
    ) {}

    /**
     * Show authorization page where user selects which restaurant to connect
     */
    public function authorize(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'state' => 'nullable|string',
            'restaurant_id' => 'required|string',
            'restaurant_name' => 'required|string',
        ]);

        // Verify client exists
        $client = $this->clients->find($request->client_id);
        if (!$client || $client->revoked) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        // Check redirect URI matches (redirect_uris is a JSON array in newer Passport versions)
        $allowedRedirects = $client->redirect_uris ?? [];
        if (is_string($allowedRedirects)) {
            $allowedRedirects = json_decode($allowedRedirects, true) ?? [$allowedRedirects];
        }

        if (!in_array($request->redirect_uri, $allowedRedirects)) {
            return response()->json(['error' => 'invalid_redirect_uri'], 400);
        }

        // Store authorization request in session
        session([
            'oauth_authorize' => [
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'state' => $request->state,
                'restaurant_id' => $request->restaurant_id,
                'restaurant_name' => $request->restaurant_name,
            ]
        ]);

        // If user not logged in, redirect to login
        if (!Auth::check()) {
            return redirect()->route('login', ['intended' => route('oauth.authorize.show')]);
        }

        return redirect()->route('oauth.authorize.show');
    }

    /**
     * Show the authorization consent form
     */
    public function showAuthorize(Request $request)
    {
        $authData = session('oauth_authorize');
        if (!$authData) {
            return redirect()->route('dashboard')->with('error', 'Yetkilendirme isteği bulunamadı.');
        }

        $client = $this->clients->find($authData['client_id']);

        return view('oauth.authorize', [
            'client' => $client,
            'restaurantId' => $authData['restaurant_id'],
            'restaurantName' => $authData['restaurant_name'],
            'state' => $authData['state'],
            'redirectUri' => $authData['redirect_uri'],
        ]);
    }

    /**
     * Handle authorization approval
     */
    public function approveAuthorize(Request $request)
    {
        $authData = session('oauth_authorize');
        if (!$authData) {
            return response()->json(['error' => 'session_expired'], 400);
        }

        $user = Auth::user();

        // Check if connection already exists
        $existingConnection = RestaurantConnection::where('user_id', $user->id)
            ->where('external_restaurant_id', $authData['restaurant_id'])
            ->where('external_platform', 'seferxyemek')
            ->first();

        if ($existingConnection) {
            // Update existing connection
            $existingConnection->update([
                'external_restaurant_name' => $authData['restaurant_name'],
                'oauth_client_id' => $authData['client_id'],
                'is_active' => true,
                'connected_at' => now(),
            ]);
            $connection = $existingConnection;
        } else {
            // Create new connection
            $connection = RestaurantConnection::create([
                'user_id' => $user->id,
                'external_restaurant_id' => $authData['restaurant_id'],
                'external_restaurant_name' => $authData['restaurant_name'],
                'external_platform' => 'seferxyemek',
                'oauth_client_id' => $authData['client_id'],
                'auto_accept' => true,
                'is_active' => true,
                'connected_at' => now(),
            ]);
        }

        // Generate webhook secret (will be sent securely via token exchange, not in URL)
        $webhookSecret = $connection->generateWebhookSecret();

        // Generate authorization code
        $code = Str::random(40);

        // Store code temporarily (expires in 10 minutes)
        // Include webhook_secret in code data to return it securely during token exchange
        cache()->put("oauth_code:{$code}", [
            'user_id' => $user->id,
            'client_id' => $authData['client_id'],
            'connection_id' => $connection->id,
            'redirect_uri' => $authData['redirect_uri'],
            'webhook_secret' => $webhookSecret, // Securely pass via token exchange
        ], now()->addMinutes(10));

        // Clear session
        session()->forget('oauth_authorize');

        // Build redirect URL - DO NOT include webhook_secret in URL for security
        $redirectUrl = $authData['redirect_uri'] . '?' . http_build_query([
            'code' => $code,
            'state' => $authData['state'],
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * Handle authorization denial
     */
    public function denyAuthorize(Request $request)
    {
        $authData = session('oauth_authorize');
        session()->forget('oauth_authorize');

        if (!$authData) {
            return redirect()->route('dashboard');
        }

        $redirectUrl = $authData['redirect_uri'] . '?' . http_build_query([
            'error' => 'access_denied',
            'error_description' => 'Kullanıcı yetkilendirmeyi reddetti.',
            'state' => $authData['state'],
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * Exchange authorization code for access token
     */
    public function token(Request $request)
    {
        \Log::info('OAuth token request received', [
            'grant_type' => $request->grant_type,
            'client_id' => $request->client_id,
            'has_code' => $request->has('code'),
        ]);

        try {
            $request->validate([
                'grant_type' => 'required|in:authorization_code,refresh_token',
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'code' => 'required_if:grant_type,authorization_code|string',
                'redirect_uri' => 'required_if:grant_type,authorization_code|url',
                'refresh_token' => 'required_if:grant_type,refresh_token|string',
            ]);

            // Verify client credentials
            $client = $this->clients->find($request->client_id);
            if (!$client || $client->revoked || !hash_equals($client->secret, $request->client_secret)) {
                return response()->json(['error' => 'invalid_client'], 401);
            }

            if ($request->grant_type === 'authorization_code') {
                return $this->handleAuthorizationCodeGrant($request, $client);
            }

            if ($request->grant_type === 'refresh_token') {
                return $this->handleRefreshTokenGrant($request, $client);
            }

            return response()->json(['error' => 'unsupported_grant_type'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('OAuth token error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'server_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function handleAuthorizationCodeGrant(Request $request, $client)
    {
        $codeData = cache()->get("oauth_code:{$request->code}");

        \Log::info('OAuth code exchange attempt', [
            'code' => substr($request->code, 0, 10) . '...',
            'code_data_exists' => $codeData ? 'yes' : 'no',
        ]);

        if (!$codeData) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code expired or invalid'], 400);
        }

        if ($codeData['client_id'] !== $request->client_id) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Client ID mismatch'], 400);
        }

        if ($codeData['redirect_uri'] !== $request->redirect_uri) {
            \Log::warning('Redirect URI mismatch', [
                'expected' => $codeData['redirect_uri'],
                'received' => $request->redirect_uri,
            ]);
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Redirect URI mismatch'], 400);
        }

        // Invalidate the code
        cache()->forget("oauth_code:{$request->code}");

        // Get user and create tokens
        $user = \App\Models\User::find($codeData['user_id']);
        if (!$user) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'User not found'], 400);
        }

        $connection = RestaurantConnection::find($codeData['connection_id']);
        if (!$connection) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Connection not found'], 400);
        }

        // Create access token using Passport
        \Log::info('Creating Passport token for user', ['user_id' => $user->id]);
        try {
            $tokenResult = $user->createToken('seferxyemek-api', ['external-orders']);
            $accessToken = $tokenResult->accessToken;
        } catch (\Exception $e) {
            \Log::error('Passport token creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
        $refreshToken = Str::random(64);

        // Store refresh token
        cache()->put("refresh_token:{$refreshToken}", [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'connection_id' => $connection->id,
        ], now()->addDays(30));

        \Log::info('OAuth token created successfully', [
            'user_id' => $user->id,
            'connection_id' => $connection->id,
        ]);

        // Include webhook_secret in token response (secure channel)
        $responseData = [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 31536000, // 1 year
            'refresh_token' => $refreshToken,
            'connection_id' => $connection->id,
            'user_id' => $user->id,
        ];

        // Add webhook_secret if available in code data (first-time authorization)
        if (isset($codeData['webhook_secret'])) {
            $responseData['webhook_secret'] = $codeData['webhook_secret'];
        }

        return response()->json($responseData);
    }

    protected function handleRefreshTokenGrant(Request $request, $client)
    {
        $tokenData = cache()->get("refresh_token:{$request->refresh_token}");

        if (!$tokenData || $tokenData['client_id'] !== $client->id) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        // Invalidate old refresh token
        cache()->forget("refresh_token:{$request->refresh_token}");

        $user = \App\Models\User::find($tokenData['user_id']);

        // Create new tokens
        $tokenResult = $user->createToken('seferxyemek-api', ['external-orders']);
        $accessToken = $tokenResult->accessToken;
        $newRefreshToken = Str::random(64);

        // Store new refresh token
        cache()->put("refresh_token:{$newRefreshToken}", [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'connection_id' => $tokenData['connection_id'],
        ], now()->addDays(30));

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 31536000,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    /**
     * Revoke connection and tokens
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'connection_id' => 'required|integer',
        ]);

        $user = Auth::user();
        $connection = RestaurantConnection::where('id', $request->connection_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$connection) {
            return response()->json(['error' => 'connection_not_found'], 404);
        }

        $connection->update(['is_active' => false]);

        // Revoke all tokens for this user
        $user->tokens()->delete();

        return response()->json(['message' => 'Connection revoked successfully']);
    }
}
