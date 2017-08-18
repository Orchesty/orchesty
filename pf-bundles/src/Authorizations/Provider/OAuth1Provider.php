<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 13:57
 */

namespace Hanaboso\PipesFramework\Authorizations\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Authorizations\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Commons\Redirect\RedirectInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Exception\AuthorizationException;
use OAuth;

/**
 * Class OAuth1Provider
 *
 * @package Hanaboso\PipesFramework\Authorizations\Provider
 */
class OAuth1Provider implements ProviderInterface
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
     * OAuth1Provider constructor.
     *
     * @param DocumentManager   $dm
     * @param RedirectInterface $redirect
     */
    public function __construct(DocumentManager $dm, RedirectInterface $redirect)
    {
        $this->dm       = $dm;
        $this->redirect = $redirect;
    }

    /**
     * @param OAuth1Dto $dto
     * @param string    $tokenUrl
     * @param string    $authorizeUrl
     * @param array     $scopes
     *
     * @throws AuthorizationException
     */
    public function authorize(OAuth1Dto $dto, string $tokenUrl, string $authorizeUrl, array $scopes = []): void
    {
        $client       = $this->createClient($dto);
        $requestToken = [];

        try {
            $requestToken = $client->getRequestToken($tokenUrl);
        } catch (Exception $e) {
            $this->throwException(sprintf('OAuth error: %s', $e->getMessage()));
        }

        $this->tokenAndSecretChecker($requestToken);
        $this->saveOAuthStuffs($dto->getAuthorization(), $requestToken);

        $authorizeUrl = $this->getAuthorizeUrl($authorizeUrl, $requestToken[self::OAUTH_TOKEN], $scopes);

        $this->redirect->make($authorizeUrl);
    }

    /**
     * @param OAuth1Dto $dto
     * @param array     $request
     * @param string    $accessTokenUrl
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getAccessToken(OAuth1Dto $dto, array $request, string $accessTokenUrl): array
    {
        if (!array_key_exists(self::OAUTH_VERIFIER, $request)) {
            $this->throwException(sprintf('OAuth error: Data "%s" is missing.', self::OAUTH_VERIFIER));
        }

        $this->tokenAndSecretChecker($dto->getAuthorization()->getToken());

        $client = $this->createClient($dto);
        $client->setToken(
            $dto->getAuthorization()->getToken()[self::OAUTH_TOKEN],
            $dto->getAuthorization()->getToken()[self::OAUTH_TOKEN_SECRET]
        );

        $token = [];
        try {
            $token = $client->getAccessToken($accessTokenUrl, NULL, $request[self::OAUTH_VERIFIER]);
        } catch (Exception $e) {
            $this->throwException($e->getMessage());
        }

        return $token;
    }

    /**
     * @param OAuth1Dto $dto
     * @param string    $method
     * @param string    $url
     *
     * @return string
     * @throws AuthorizationException
     */
    public function getAuthorizeHeader(OAuth1Dto $dto, string $method, string $url): string
    {
        $this->tokenAndSecretChecker($dto->getAuthorization()->getToken());

        $client = $this->createClient($dto);
        $client->setToken(
            $dto->getAuthorization()->getToken()[self::OAUTH_TOKEN],
            $dto->getAuthorization()->getToken()[self::OAUTH_TOKEN_SECRET]
        );

        return $client->getRequestHeader($method, $url);
    }



    /**
     * ------------------------------------ HELPERS ----------------------------------------
     */

    /**
     * @param OAuth1Dto $dto
     *
     * @return OAuth
     */
    protected function createClient(OAuth1Dto $dto): OAuth
    {
        return new OAuth(
            $dto->getConsumerKey(),
            $dto->getConsumerSecret(),
            $dto->getSignatureMethod(),
            $dto->getAuthType()
        );
    }

    /**
     * @param Authorization $authorization
     * @param array         $data
     */
    private function saveOAuthStuffs(Authorization $authorization, array $data): void
    {
        $authorization->setToken(
            array_merge($authorization->getToken(), $data)
        );

        $this->dm->persist($authorization);
        $this->dm->flush($authorization);
    }

    /**
     * @param string $authorizeUrl
     * @param string $oauthToken
     * @param array  $scopes
     *
     * @return string
     */
    private function getAuthorizeUrl(string $authorizeUrl, string $oauthToken, array $scopes): string
    {
        return sprintf('%s?oauth_token=%s%s', $authorizeUrl, $oauthToken, $this->getScopes($scopes));
    }

    /**
     * @param array $scopes
     *
     * @return string
     */
    private function getScopes(array $scopes): string
    {
        if (empty($scopes)) {

            return '';
        }

        $scope = implode(',', $scopes);

        return sprintf('&scope=%s', $scope);
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
        //TODO log this
        throw new AuthorizationException($message, AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
    }

}