<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1DtoInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use OAuth;
use OAuthException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class OAuth1Provider
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
class OAuth1Provider implements OAuth1ProviderInterface, LoggerAwareInterface
{

    public const OAUTH_TOKEN        = 'oauth_token';
    public const OAUTH_TOKEN_SECRET = 'oauth_token_secret';

    private const OAUTH_VERIFIER = 'oauth_verifier';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OAuth1Provider constructor.
     *
     * @param DocumentManager   $dm
     * @param RedirectInterface $redirect
     */
    public function __construct(DocumentManager $dm, RedirectInterface $redirect)
    {
        $this->dm       = $dm;
        $this->redirect = $redirect;
        $this->logger   = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return OAuth1Provider
     */
    public function setLogger(LoggerInterface $logger): OAuth1Provider
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param OAuth1DtoInterface $dto
     * @param string             $tokenUrl
     * @param string             $authorizeUrl
     * @param string             $redirectUrl
     * @param callable           $saveOauthStuffs
     * @param array              $scopes
     *
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function authorize(
        OAuth1DtoInterface $dto,
        string $tokenUrl,
        string $authorizeUrl,
        string $redirectUrl,
        callable $saveOauthStuffs,
        array $scopes = []
    ): void
    {
        $client       = $this->createClient($dto);
        $requestToken = [];

        try {
            $requestToken = $client->getRequestToken($tokenUrl);
        } catch (Exception $e) {
            $this->throwException(sprintf('OAuth error: %s', $e->getMessage()));
        }

        $this->tokenAndSecretChecker((array) $requestToken);

        $saveOauthStuffs($this->dm, $dto, $requestToken);

        $authorizeUrl = $this->getAuthorizeUrl($authorizeUrl, $redirectUrl, $requestToken[self::OAUTH_TOKEN], $scopes);

        $this->redirect->make($authorizeUrl);
    }

    /**
     * @param OAuth1DtoInterface $dto
     * @param array              $request
     * @param string             $accessTokenUrl
     *
     * @return array
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function getAccessToken(OAuth1DtoInterface $dto, array $request, string $accessTokenUrl): array
    {
        if (!array_key_exists(self::OAUTH_VERIFIER, $request)) {
            $this->throwException(sprintf('OAuth error: Data "%s" is missing.', self::OAUTH_VERIFIER));
        }

        $this->tokenAndSecretChecker($dto->getToken());

        $client = $this->createClient($dto);
        $client->setToken(
            $dto->getToken()[self::OAUTH_TOKEN],
            $dto->getToken()[self::OAUTH_TOKEN_SECRET]
        );

        $token = [];
        try {
            $token = $client->getAccessToken($accessTokenUrl, NULL, $request[self::OAUTH_VERIFIER]);
        } catch (Exception $e) {
            $this->throwException($e->getMessage());
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
            $dto->getToken()[self::OAUTH_TOKEN_SECRET]
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
            $dto->getAuthType()
        );
    }

    /**
     * @param string $authorizeUrl
     * @param string $redirectUrl
     * @param string $oauthToken
     * @param array  $scopes
     *
     * @return string
     */
    private function getAuthorizeUrl(
        string $authorizeUrl,
        string $redirectUrl,
        string $oauthToken,
        array $scopes
    ): string
    {
        return sprintf(
            '%s?oauth_callback=%s&oauth_token=%s%s',
            $authorizeUrl,
            $redirectUrl,
            $oauthToken,
            ScopeFormatter::getScopes($scopes)
        );
    }

    /**
     * @param array $data
     *
     * @throws AuthorizationException
     */
    private function tokenAndSecretChecker(array $data): void
    {
        if (
            !array_key_exists(self::OAUTH_TOKEN_SECRET, $data) ||
            !array_key_exists(self::OAUTH_TOKEN, $data)
        ) {
            $this->throwException(
                sprintf(
                    'OAuth error: Data "%s" or "%s" is missing.',
                    self::OAUTH_TOKEN_SECRET,
                    self::OAUTH_TOKEN
                )
            );
        }
    }

    /**
     * @param string $message
     *
     * @throws AuthorizationException
     */
    private function throwException(string $message): void
    {
        $this->logger->error($message);
        throw new AuthorizationException($message, AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
    }

}
