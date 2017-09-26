<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.9.17
 * Time: 9:35
 */

namespace Hanaboso\PipesFramework\Authorization\Provider;

use Exception;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2DtoInterface;
use Hanaboso\PipesFramework\Authorization\Utils\ScopeFormater;
use Hanaboso\PipesFramework\Authorization\Wrapper\OAuth2Wrapper;
use Hanaboso\PipesFramework\Commons\Redirect\RedirectInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OAuth2Provider constructor.
     *
     * @param RedirectInterface $redirect
     */
    public function __construct(RedirectInterface $redirect)
    {
        $this->redirect = $redirect;
        $this->logger   = new NullLogger();
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
     *
     * @throws AuthorizationException
     */
    public function authorize(OAuth2DtoInterface $dto, array $scopes = []): void
    {
        $client           = $this->createClient($dto);
        $authorizationUrl = $this->getAuthorizeUrl($client->getAuthorizationUrl(), $scopes);

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
            'redirectUri'             => $dto->getRedirectUrl(),
            'urlAuthorize'            => $dto->getAuthorizeUrl(),
            'urlAccessToken'          => $dto->getTokenUrl(),
            'urlResourceOwnerDetails' => $dto->getAuthorizeUrl(),
        ]);
    }

    /**
     * @param string $authorizeUrl
     * @param array  $scopes
     *
     * @return string
     */
    private function getAuthorizeUrl(string $authorizeUrl, array $scopes): string
    {
        return sprintf('%s%s', $authorizeUrl, ScopeFormater::getScopes($scopes));
    }

    /**
     * @param OAuth2DtoInterface $dto
     * @param string             $grant
     * @param array              $data
     *
     * @return array
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