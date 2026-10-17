<?php

declare(strict_types=1);

namespace App\User\OAuth;

use App\Shared\Http\NativeHttpClient;
use JsonException;
use RuntimeException;

final readonly class OAuthService
{
    public function __construct(private NativeHttpClient $httpClient)
    {
    }

    public function authorizationUrl(string $provider, string $clientId, string $redirectUri, string $state): string
    {
        $query = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
        ];

        if ($provider === 'google') {
            $query['scope'] = 'openid email profile';
            $query['access_type'] = 'online';
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        if ($provider === 'facebook') {
            $query['scope'] = 'email,public_profile';
            return 'https://www.facebook.com/dialog/oauth?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        throw new RuntimeException('Unsupported OAuth provider.');
    }

    public function fetchProfile(
        string $provider,
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri,
    ): OAuthProfile {
        return match ($provider) {
            'google' => $this->fetchGoogleProfile($code, $clientId, $clientSecret, $redirectUri),
            'facebook' => $this->fetchFacebookProfile($code, $clientId, $clientSecret, $redirectUri),
            default => throw new RuntimeException('Unsupported OAuth provider.'),
        };
    }

    private function fetchGoogleProfile(
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri,
    ): OAuthProfile {
        $tokenResponse = $this->httpClient->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        $tokenData = $this->decodeJson($tokenResponse->body);
        $accessToken = $this->requiredString($tokenData, 'access_token');

        $profileResponse = $this->httpClient->get(
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['Authorization' => 'Bearer ' . $accessToken, 'Accept' => 'application/json'],
        );
        $profile = $this->decodeJson($profileResponse->body);

        return new OAuthProfile(
            $this->requiredString($profile, 'email'),
            $this->requiredString($profile, 'name'),
            $this->optionalString($profile, 'picture'),
        );
    }

    private function fetchFacebookProfile(
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri,
    ): OAuthProfile {
        $tokenResponse = $this->httpClient->postForm('https://graph.facebook.com/oauth/access_token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ], ['Accept' => 'application/json']);
        $tokenData = $this->decodeJson($tokenResponse->body);
        $accessToken = $this->requiredString($tokenData, 'access_token');

        $profileResponse = $this->httpClient->get(
            'https://graph.facebook.com/me?fields=id,name,email,picture.type(large)&access_token=' . rawurlencode($accessToken),
            ['Accept' => 'application/json'],
        );
        $profile = $this->decodeJson($profileResponse->body);
        $avatar = null;
        $picture = $profile['picture'] ?? null;
        if (is_array($picture) && isset($picture['data']) && is_array($picture['data'])) {
            $avatarValue = $picture['data']['url'] ?? null;
            $avatar = is_string($avatarValue) ? $avatarValue : null;
        }

        return new OAuthProfile(
            $this->requiredString($profile, 'email'),
            $this->requiredString($profile, 'name'),
            $avatar,
        );
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $body): array
    {
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OAuth provider returned invalid JSON.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('OAuth provider returned an invalid response.');
        }

        if (isset($data['error'])) {
            throw new RuntimeException('OAuth provider rejected the request.');
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('OAuth response did not include %s.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }
}
