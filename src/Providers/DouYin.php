<?php

namespace Iphpjs\Socialite\Providers;

use Iphpjs\Socialite\Exceptions\AuthorizeFailedException;
use Iphpjs\Socialite\Exceptions\InvalidArgumentException;
use Iphpjs\Socialite\User;

/**
 * @see http://open.douyin.com/platform
 * @see https://open.douyin.com/platform/doc/OpenAPI-overview
 */
class DouYin extends Base
{
    public const NAME = 'douyin';
    protected string $baseUrl = 'https://open.douyin.com';
    protected array $scopes = ['user_info'];
    protected ?string $openId;

    protected function getAuthUrl(): string
    {
        return $this->buildAuthUrlFromBase($this->baseUrl . '/platform/oauth/connect/');
    }

    /**
     * @return array
     */
    public function getCodeFields(): array
    {
        return [
            'client_key' => $this->getClientId(),
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'response_type' => 'code',
        ];
    }

    protected function getTokenUrl(): string
    {
        return $this->baseUrl . '/oauth/access_token/';
    }

    /**
     * @param  string  $code
     *
     * @return array
     * @throws \Iphpjs\Socialite\Exceptions\AuthorizeFailedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     */
    public function tokenFromCode($code): array
    {
        $response = $this->getHttpClient()->get(
            $this->getTokenUrl(),
            [
                'query' => $this->getTokenFields($code),
            ]
        );

        $response = \json_decode($response->getBody()->getContents(), true) ?? [];

        if (empty($response['data'])) {
            throw new AuthorizeFailedException('Invalid token response', $response);
        }

        $this->withOpenId($response['data']['openid']);

        return $this->normalizeAccessTokenResponse($response['data']);
    }

    /**
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code): array
    {
        return [
            'client_key' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * @param  string  $token
     *
     * @return array
     * @throws \Iphpjs\Socialite\Exceptions\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     */
    protected function getUserByToken(string $token): array
    {
        $userUrl = $this->baseUrl . '/oauth/userinfo/';

        if (empty($this->openId)) {
            throw new InvalidArgumentException('please set open_id before your query.');
        }

        $response = $this->getHttpClient()->get(
            $userUrl,
            [
                'query' => [
                    'access_token' => $token,
                    'open_id' => $this->openId,
                ],
            ]
        );

        return \json_decode($response->getBody(), true) ?? [];
    }

    /**
     * @param array $user
     *
     * @return \Iphpjs\Socialite\User
     */
    protected function mapUserToObject(array $user): User
    {
        return new User(
            [
                'id' => $user['open_id'] ?? null,
                'name' => $user['nickname'] ?? null,
                'nickname' => $user['nickname'] ?? null,
                'avatar' => $user['avatar'] ?? null,
                'email' => $user['email'] ?? null,
            ]
        );
    }

    public function withOpenId(string $openId): self
    {
        $this->openId = $openId;

        return $this;
    }
}
