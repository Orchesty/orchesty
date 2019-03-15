<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Provider;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Utils\Base64;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2DtoInterface;
use Hanaboso\PipesFramework\Authorization\Utils\ScopeFormatter;
use Hanaboso\PipesFramework\Authorization\Wrapper\OAuth2Wrapper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function GuzzleHttp\Psr7\build_query;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Class OAuth2Provider
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider
 */
class OAuth2Provider implements OAuth2ProviderInterface, LoggerAwareInterface
{

    public const  REFRESH_TOKEN     = 'refresh_token';
    public const  ACCESS_TOKEN      = 'access_token';
    public const  EXPIRES           = 'expires';
    private const RESOURCE_OWNER_ID = 'resource_owner_id';
    private const ACCESS_TYPE       = 'access_type';
    private const STATE             = 'state';

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $backend;

    /**
     * OAuth2Provider constructor.
     *
     * @param RedirectInterface $redirect
     * @param string            $backend
     */
    public function __construct(RedirectInterface $redirect, string $backend)
    {
        $this->redirect = $redirect;
        $this->logger   = new NullLogger();
        $this->backend  = $backend;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return OAuth2Provider
     */
    public function setLogger(LoggerInterface $logger): OAuth2Provider
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $scopes
     * @param string             $separator
     */
    public function authorize(
        OAuth2DtoInterface $dto,
        array $scopes = [],
        string $separator = ScopeFormatter::COMMA
    ): void
    {
        $client           = $this->createClient($dto);
        $authorizationUrl = $this->getAuthorizeUrl($dto, $client->getAuthorizationUrl(), $scopes, $separator);

        $this->redirect->make($authorizationUrl);
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $request
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth2DtoInterface $dto, array $request): array
    {
        if (!isset($request['code'])) {
            $this->throwException('Data from input is invalid! Field "code" is missing!');
        }

        return $this->getTokenByGrant($dto, 'authorization_code', ['code' => $request['code']]);
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param array              $token
     *
     * @return array
     * @throws AuthorizationException
     */
    public function refreshAccessToken(OAuth2DtoInterface $dto, array $token): array
    {

        if (!isset($token[self::REFRESH_TOKEN])) {
            $this->throwException('Refresh token not found! Refresh is not possible.');
        }

        $oldRefreshToken = $token[self::REFRESH_TOKEN];
        $accessToken     = $this->getTokenByGrant(
            $dto,
            self::REFRESH_TOKEN,
            [self::REFRESH_TOKEN => $oldRefreshToken]
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
     * -------------------------------------------- HELPERS --------------------------------------
     */

    /**
     * @param OAuth2DtoInterface $dto
     *
     * @return OAuth2Wrapper
     */
    protected function createClient(OAuth2DtoInterface $dto): OAuth2Wrapper
    {
        return new OAuth2Wrapper([
            'clientId'                => $dto->getClientId(),
            'clientSecret'            => $dto->getClientSecret(),
            'redirectUri'             => rtrim($this->backend, '/') . '/' . ltrim($dto->getRedirectUrl(), '/'),
            'urlAuthorize'            => $dto->getAuthorizeUrl(),
            'urlAccessToken'          => $dto->getTokenUrl(),
            'urlResourceOwnerDetails' => $dto->getAuthorizeUrl(),
        ]);
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param string             $authorizeUrl
     * @param array              $scopes
     * @param string             $separator
     *
     * @return string
     */
    private function getAuthorizeUrl(
        OAuth2DtoInterface $dto,
        string $authorizeUrl,
        array $scopes,
        string $separator = ScopeFormatter::COMMA
    ): string
    {
        $state = NULL;
        if (!$dto->isCustomApp()) {
            $state = Base64::base64UrlEncode($dto->getUser() . ':' . $dto->getSystemKey());
        }

        $scopes = ScopeFormatter::getScopes($scopes, $separator);
        $url    = sprintf('%s%s', $authorizeUrl, $scopes);
        $query  = parse_query($url);
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

        return sprintf('%s?%s', $host[0], build_query($query, FALSE));
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param string             $grant
     * @param array              $data
     *
     * @return array
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

            $this->throwException($message);
        }

        return $token;
    }

    /**
     * @param string $message
     *
     * @throws AuthorizationException
     */
    private function throwException(string $message): void
    {
        $this->logger->error($message);
        throw new AuthorizationException($message, AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
    }

}
