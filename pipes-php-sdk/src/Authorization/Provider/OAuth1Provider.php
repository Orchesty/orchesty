<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1DtoInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use OAuth;
use OAuthException;
use Psr\Log\LoggerAwareInterface;

/**
 * Class OAuth1Provider
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
final class OAuth1Provider extends OAuthProviderAbstract implements OAuth1ProviderInterface, LoggerAwareInterface
{

    public const string OAUTH_TOKEN        = 'oauth_token';
    public const string OAUTH_TOKEN_SECRET = 'oauth_token_secret';

    private const string OAUTH_VERIFIER = 'oauth_verifier';

    /**
     * OAuth1Provider constructor.
     *
     * @param string                       $backend
     * @param ApplicationInstallRepository $applicationInstallRepository
     */
    public function __construct(
        string $backend,
        private readonly ApplicationInstallRepository $applicationInstallRepository,
    )
    {
        parent::__construct($backend);
    }

    /**
     * @param OAuth1DtoInterface $dto
     * @param string             $tokenUrl
     * @param string             $authorizeUrl
     * @param callable           $saveOauthStuffs
     * @param mixed[]            $scopes
     *
     * @return string
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function authorize(
        OAuth1DtoInterface $dto,
        string $tokenUrl,
        string $authorizeUrl,
        callable $saveOauthStuffs,
        array $scopes = [],
    ): string
    {
        $client       = $this->createClient($dto);
        $requestToken = [];

        try {
            /** @var mixed[] $requestToken */
            $requestToken = $client->getRequestToken($tokenUrl);
        } catch (Exception $e) {
            $this->throwException(
                sprintf('OAuth error: %s', $e->getMessage()),
                AuthorizationException::AUTHORIZATION_OAUTH1_ERROR,
            );
        }

        $this->tokenAndSecretChecker($requestToken);

        $saveOauthStuffs($this->applicationInstallRepository, $dto, $requestToken);

        return $this->getAuthorizeUrl(
            $authorizeUrl,
            $this->getRedirectUri(),
            $requestToken[self::OAUTH_TOKEN],
            $scopes,
        );
    }

    /**
     * @param OAuth1DtoInterface $dto
     * @param mixed[]            $request
     * @param string             $accessTokenUrl
     *
     * @return mixed[]
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function getAccessToken(OAuth1DtoInterface $dto, array $request, string $accessTokenUrl): array
    {
        if (!array_key_exists(self::OAUTH_VERIFIER, $request)) {
            $this->throwException(
                sprintf('OAuth error: Data "%s" is missing.', self::OAUTH_VERIFIER),
                AuthorizationException::AUTHORIZATION_OAUTH1_ERROR,
            );
        }

        $this->tokenAndSecretChecker($dto->getToken());

        $client = $this->createClient($dto);
        $client->setToken(
            $dto->getToken()[self::OAUTH_TOKEN],
            $dto->getToken()[self::OAUTH_TOKEN_SECRET],
        );

        $token = [];
        try {
            $token = $client->getAccessToken($accessTokenUrl, NULL, $request[self::OAUTH_VERIFIER]);
        } catch (Exception $e) {
            $this->throwException($e->getMessage(), AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        return (array) $token;
    }

    /**
     * @param OAuth1DtoInterface $dto
     * @param string             $method
     * @param string             $url
     *
     * @return string
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function getAuthorizeHeader(OAuth1DtoInterface $dto, string $method, string $url): string
    {
        $this->tokenAndSecretChecker($dto->getToken());

        $client = $this->createClient($dto);
        $client->setToken(
            $dto->getToken()[self::OAUTH_TOKEN],
            $dto->getToken()[self::OAUTH_TOKEN_SECRET],
        );

        return (string) $client->getRequestHeader($method, $url);
    }

    /**
     * ------------------------------------ HELPERS ----------------------------------------
     */

    /**
     * @param OAuth1DtoInterface $dto
     *
     * @return OAuth
     * @throws OAuthException
     */
    protected function createClient(OAuth1DtoInterface $dto): OAuth
    {
        return new OAuth(
            $dto->getConsumerKey(),
            $dto->getConsumerSecret(),
            $dto->getSignatureMethod(),
            $dto->getAuthType(),
        );
    }

    /**
     * @param string   $authorizeUrl
     * @param string   $redirectUrl
     * @param string   $oauthToken
     * @param string[] $scopes
     *
     * @return string
     */
    private function getAuthorizeUrl(
        string $authorizeUrl,
        string $redirectUrl,
        string $oauthToken,
        array $scopes,
    ): string
    {
        return sprintf(
            '%s?oauth_callback=%s&oauth_token=%s%s',
            $authorizeUrl,
            $redirectUrl,
            $oauthToken,
            ScopeFormatter::getScopes($scopes),
        );
    }

    /**
     * @param mixed[] $data
     *
     * @throws AuthorizationException
     */
    private function tokenAndSecretChecker(array $data): void
    {
        if (!array_key_exists(self::OAUTH_TOKEN_SECRET, $data) || !array_key_exists(self::OAUTH_TOKEN, $data)) {
            $this->throwException(
                sprintf(
                    'OAuth error: Data "%s" or "%s" is missing.',
                    self::OAUTH_TOKEN_SECRET,
                    self::OAUTH_TOKEN,
                ),
                AuthorizationException::AUTHORIZATION_OAUTH1_ERROR,
            );
        }
    }

}
