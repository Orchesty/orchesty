<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Exception;
use GuzzleHttp\Psr7\Query;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2DtoInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use Hanaboso\PipesPhpSdk\Authorization\Wrapper\OAuth2Wrapper;
use Hanaboso\Utils\String\Base64;
use Psr\Log\LoggerAwareInterface;

/**
 * Class OAuth2Provider
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
final class OAuth2Provider extends OAuthProviderAbstract implements OAuth2ProviderInterface, LoggerAwareInterface
{

    public const string  REFRESH_TOKEN     = 'refresh_token';
    public const string  ACCESS_TOKEN      = 'access_token';
    public const string  EXPIRES           = 'expires';
    private const string RESOURCE_OWNER_ID = 'resource_owner_id';
    private const string ACCESS_TYPE       = 'access_type';
    private const string STATE             = 'state';

    /**
     * @param OAuth2DtoInterface $dto
     * @param string[]           $scopes
     * @param string             $separator
     *
     * @return string
     */
    public function authorize(
        OAuth2DtoInterface $dto,
        array $scopes = [],
        string $separator = ScopeFormatter::COMMA,
    ): string
    {
        $client = $this->createClient($dto);

        return $this->getAuthorizeUrl($dto, $client->getAuthorizationUrl(), $scopes, $separator);
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param mixed[]            $request
     *
     * @return mixed[]
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth2DtoInterface $dto, array $request): array
    {
        if (!isset($request['code'])) {
            $this->throwException(
                'Data from input is invalid! Field "code" is missing!',
                AuthorizationException::AUTHORIZATION_OAUTH2_ERROR,
            );
        }

        return $this->getTokenByGrant($dto, 'authorization_code', $request);
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param mixed[]            $token
     *
     * @return mixed[]
     * @throws AuthorizationException
     */
    public function refreshAccessToken(OAuth2DtoInterface $dto, array $token): array
    {

        if (!isset($token[self::REFRESH_TOKEN])) {
            $this->throwException(
                'Refresh token not found! Refresh is not possible.',
                AuthorizationException::AUTHORIZATION_OAUTH2_ERROR,
            );
        }

        $oldRefreshToken = $token[self::REFRESH_TOKEN];
        $accessToken     = $this->getTokenByGrant(
            $dto,
            self::REFRESH_TOKEN,
            [self::REFRESH_TOKEN => $oldRefreshToken],
        );

        $opts = [];
        if (!isset($accessToken[self::REFRESH_TOKEN])) {
            $opts[self::REFRESH_TOKEN]     = $oldRefreshToken;
            $opts[self::ACCESS_TOKEN]      = $accessToken[self::ACCESS_TOKEN] ?? NULL;
            $opts[self::EXPIRES]           = $accessToken[self::EXPIRES] ?? NULL;
            $opts[self::RESOURCE_OWNER_ID] = $accessToken[self::RESOURCE_OWNER_ID] ?? NULL;
            $accessToken                   = array_merge($opts, $accessToken);
        }

        return $accessToken;
    }

    /**
     * @param OAuth2DtoInterface $dto
     *
     * @return string
     */
    public static function stateEncode(OAuth2DtoInterface $dto): string
    {
        return Base64::base64UrlEncode(sprintf('%s:%s', $dto->getUser(), $dto->getApplicationKey()));
    }

    /**
     * @param string $state
     *
     * @return mixed[]
     */
    public static function stateDecode(string $state): array
    {
        $params = explode(':', Base64::base64UrlDecode($state));

        return [$params[0], $params[1] ?? ''];
    }

    /**
     * -------------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param OAuth2DtoInterface $dto
     *
     * @return OAuth2Wrapper
     */
    protected function createClient(OAuth2DtoInterface $dto): OAuth2Wrapper
    {
        return new OAuth2Wrapper(
            [
                'clientId'                => $dto->getClientId(),
                'clientSecret'            => $dto->getClientSecret(),
                'redirectUri'             => $dto->isRedirectUrl() ? $dto->getRedirectUrl() : $this->getRedirectUri(),
                'urlAccessToken'          => $dto->getTokenUrl(),
                'urlAuthorize'            => $dto->getAuthorizeUrl(),
                'urlResourceOwnerDetails' => $dto->getAuthorizeUrl(),
            ],
        );
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param string             $authorizeUrl
     * @param string[]           $scopes
     * @param string             $separator
     *
     * @return string
     */
    private function getAuthorizeUrl(
        OAuth2DtoInterface $dto,
        string $authorizeUrl,
        array $scopes,
        string $separator = ScopeFormatter::COMMA,
    ): string
    {
        $state = NULL;
        if (!$dto->isCustomApp()) {
            $state = self::stateEncode($dto);
        }

        $scopes = ScopeFormatter::getScopes($scopes, $separator);
        $url    = sprintf('%s%s', $authorizeUrl, $scopes);
        $query  = Query::parse($url);
        $host   = key($query);
        $v      = reset($query);
        unset($query[$host]);

        $host = explode('?', (string) $host);
        if (isset($host[1])) {
            $query[$host[1]] = $v;
        }

        $query[self::ACCESS_TYPE] = 'offline';

        if ($state) {
            $query[self::STATE] = $state;
        }

        return sprintf('%s?%s', $host[0], Query::build($query, FALSE));
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param string             $grant
     * @param mixed[]            $data
     *
     * @return mixed[]
     * @throws AuthorizationException
     */
    private function getTokenByGrant(OAuth2DtoInterface $dto, string $grant, array $data = []): array
    {
        $client = $this->createClient($dto);

        $token = [];
        try {
            $token = $client->getAccessToken($grant, $data)->jsonSerialize();
        } catch (Exception $e) {
            $message = sprintf('OAuth2 Error: %s', $e->getMessage());
            $this->logger->error($message, ['exception' => $e]);

            $this->throwException($message, AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
        }

        return $token;
    }

}
